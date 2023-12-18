<?php namespace LukeTowers\EFPDF;

use Event;
use Backend;
use System\Classes\PluginBase;

use LukeTowers\EFPDF\Models\Pdf;
use LukeTowers\EasyForms\Models\Form;
use LukeTowers\EasyForms\Controllers\Forms;
use LukeTowers\EasyForms\Models\Notification;

/**
 * EasyForms PDF Plugin Information File
 *
 * @TODO:
 * - Add ability to define global templates (plus define a few initial ones to use)
 *      - Templates should be able to be dealt with like mail templates are (database layer over filesystem backing)
 * - Add previewing ability to templates
 * - Add ability to generate a PDF straight from an entries detailed view
 * - Add more configuration on the final PDF generated (look at GravityPDF for inspiration)
 * - Add composer.json file
 */
class Plugin extends PluginBase
{
    /**
     * @var array Plugin dependencies
     */
    public $require = [
        'LukeTowers.SnappyPDF',
        'LukeTowers.EasyForms',
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Easy Forms PDF',
            'description' => 'Easy Forms add-on to automatically generate PDFs from submitted forms',
            'author'      => 'Luke Towers',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Form::extend(function ($model) {
            $model->hasMany = array_merge($model->hasMany, [
                'pdfs' => [Pdf::class],
            ]);
        });

        Notification::extend(function ($model) {
            $model->belongsToMany = array_merge($model->belongsToMany, [
                'pdfs' => [Pdf::class, 'table' => 'luketowers_easyforms_notification_pdfs'],
            ]);
        });

        Forms::extend(function ($controller) {
            $relationConfig = $controller->mergeConfig($controller->relationConfig ?: 'config_relation.yaml', '$/luketowers/efpdf/yaml/controller.form.relationconfig.yaml');
            if ($controller->propertyExists('relationConfig')) {
                $controller->relationConfig = $relationConfig;
            } else {
                $controller->addDynamicProperty('relationConfig', $relationConfig);
            }
        });

        Forms::extendFormFields(function ($form, $model, $context) {
            if ($form->isNested || !($model instanceof Form)) {
                return;
            }

            $form->addSecondaryTabFields([
                'pdfs' => [
                    'type' => 'partial',
                    'path' => '$/luketowers/efpdf/partials/field.pdfs.htm',
                    'tab'  => 'luketowers.efpdf::lang.models.pdf.label_plural',
                ],
            ]);
        });

        Event::listen('luketowers.easyforms.notification.beforeSend', function ($notification, &$vars, &$data, &$files) {
            // Retreive the PDFs attached to this notification
            $pdfs = $notification->pdfs;

            if ($pdfs->count()) {
                foreach ($pdfs as $pdf) {
                    // Generate the PDF and add it for attaching to the email
                    $pdf->parseSettings($vars);
                    $path = $pdf->generateTempFile($vars);
                    $files[$path] = ['as' => $pdf->data['filename'] . '.pdf', 'mime' => 'application/pdf'];
                }
            }
        });
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'luketowers.efpdf.some_permission' => [
                'tab' => 'EFPDF',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'efpdf' => [
                'label'       => 'EFPDF',
                'url'         => Backend::url('luketowers/efpdf/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['luketowers.efpdf.*'],
                'order'       => 500,
            ],
        ];
    }

    // public function previewPdf()
    // {
    //     $entry = Entry::find($id);

    //     // Flatten and then expand the widgetData to allow Twig to access it
    //     // with dot syntax
    //     $fieldsData = $entry->data;
    //     $expandedFields = [];
    //     foreach ($fieldsData as $field => $value) {
    //         array_set($expandedFields, $field, $value);
    //     }

    //     // Load field configuration
    //     $fieldConfig = $entry->form->getFlattenedFields();
    //     foreach ($fieldConfig as $field => &$config) {
    //         if (is_array(@$expandedFields[$field])) {
    //             continue;
    //         }
    //         $config['value'] = @$expandedFields[$field];
    //     }

    //     // Prepare the variables
    //     $vars = [
    //         'ip_address'   => $entry->ip_address,
    //         'user_agent'   => $entry->user_agent,
    //         'source_url'   => $entry->source_url,
    //         'form_title'   => $entry->form->name,
    //         'entry'        => $entry,
    //         'fields'       => $fieldsData,
    //         'field_config' => $fieldConfig,
    //         'settings'     => @$entry->form->data['settings'] ?: [],
    //     ];

    //     $this->layout = null;

    //     $html = Twig::parse(file_get_contents(plugins_path('luketowers/easyforms/components/partials/house_application.htm')), $vars);

    //     // return $html;

    //     $pdf = SnappyPDF::loadHTML($html)
    //         // ->setOption('margin-top', 0)
    //         // ->setOption('margin-bottom', 0)
    //         // ->setOption('margin-left', 0)
    //         // ->setOption('margin-right', 0)
    //         ->setPaper('letter')
    //         ->output();

    //     return Response::make($pdf, 200, [
    //         'Content-Type'        => 'application/pdf',
    //         'Content-Disposition' => "filename=application.pdf",
    //     ]);
    // }
}
