<?php namespace LukeTowers\EasyForms\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreatePdfsTable extends Migration
{
    public function up()
    {
        Schema::create('luketowers_easyforms_pdfs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('form_id')->unsigned()->index()->nullable();
            $table->string('name');
            $table->string('template');
            $table->longText('custom_template')->nullable();
            $table->text('data')->nullable();
            $table->timestamps();
        });

        Schema::create('luketowers_easyforms_notification_pdfs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('notification_id')->unsigned();
            $table->integer('pdf_id')->unsigned();
            $table->primary(['notification_id', 'pdf_id'], 'luketowers_easyforms_notification_pdfs_primary_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('luketowers_easyforms_pdfs');
        Schema::dropIfExists('luketowers_easyforms_notification_pdfs');
    }
}
