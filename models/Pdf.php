<?php namespace LukeTowers\EFPDF\Models;

use App;
use Twig;
use Model;
use SnappyPDF;

use LukeTowers\EasyForms\Models\Form;
use LukeTowers\EasyForms\Models\Notification;

/**
 * Pdf Model
 *
 * @TODO:
 * - Add support for attaching notifications on create and for picking up deferred notifications as well
 * - Set an appropriate filename for the generated PDF. You should exclude the .pdf extension from the name. The following are invalid characters and will be converted to an underscore _ when the PDF is generated: / \ " * ? | :
 *
 */
class Pdf extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'luketowers_easyforms_pdfs';

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'name' => 'required',
        'template' => 'required',
        'custom_template' => 'required_if:template,custom',
    ];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = ['data'];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'form' => [Form::class],
    ];
    public $belongsToMany = [
        'notifications' => [Notification::class, 'table' => 'luketowers_easyforms_notification_pdfs'],
    ];

    /**
     * Get the available templates that this PDF can use to be rendered
     *
     * @return array ['key' => 'label']
     */
    public function getTemplateOptions()
    {
        return ['custom' => 'luketowers.efpdf::lang.models.pdf.templates.custom'];
    }

    /**
     * Get the available notifications that this PDF can be attached to
     *
     * @return array ['id' => 'name']
     */
    public function getNotificationsOptions()
    {
        return $this->form ? $this->form->notifications()->lists('name', 'id') : [];
    }

    /**
     * Parse settings for placeholders using the provided variables
     *
     * @param array $vars
     * @return void
     */
    public function parseSettings(array $vars)
    {
        $data = $this->data;

        $parsableSettings = [
            'filename',
        ];

        $twig = App::make('luketowers.easyforms.twig.environment');
        $parse = function ($contents, $vars) use ($twig) {
            $template = $twig->createTemplate($contents);
            return $template->render($vars);
        };

        foreach ($parsableSettings as $setting) {
            if (!empty(array_get($data, $setting))) {
                array_set($data, $setting, $parse(array_get($data, $setting), $vars));
            }
        }

        $this->data = $data;
    }

    /**
     * Generate a PDF file from a provided form entry record
     *
     * @param array $vars Variables to make available to the template
     * @return string The path to the generated temporary PDF file
     */
    public function generateTempFile(array $vars)
    {
        if ($this->template === 'custom') {
            $template = $this->custom_template;
        }

        // $html = Twig::parse($twig, $vars);

        $twig = App::make('luketowers.easyforms.twig.environment');
        $parse = function ($contents, $vars) use ($twig) {
            $template = $twig->createTemplate($contents);
            return $template->render($vars);
        };

        $html = $parse($template, $vars);

        $pdf = SnappyPDF::loadHTML($html)
            // ->setOption('margin-top', 0)
            // ->setOption('margin-bottom', 0)
            // ->setOption('margin-left', 0)
            // ->setOption('margin-right', 0)
            ->setPaper('letter')
            ->output();

        $path = tempnam(sys_get_temp_dir(), 'PDF');
        file_put_contents($path, $pdf);
        return $path;
    }
}
