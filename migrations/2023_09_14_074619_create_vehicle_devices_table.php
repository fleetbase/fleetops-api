<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehicleDevicesTable extends Migration
{
  public function up()
  {
    Schema::create('vehicle_devices', function (Blueprint $table) {
      $table->uuid('uuid')->primary();
      $table->uuid('vehicle_uuid');
      $table->foreign('vehicle_uuid')->references('uuid')->on('vehicles');
      $table->string('device_type');
      $table->string('device_name');
      $table->string('device_model')->nullable();
      $table->string('manufacturer')->nullable();
      $table->string('serial_number')->nullable();
      $table->date('installation_date')->nullable();
      $table->date('last_maintenance_date')->nullable();
      $table->json('meta')->nullable();
      $table->string('status')->nullable();
      $table->string('data_frequency')->nullable();
      $table->text('notes')->nullable();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('vehicle_devices');
  }
}
