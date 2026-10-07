<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->decimal('prix', 12, 2);
            $t->timestamps();
        });

        Schema::create('factures', function (Blueprint $t) {
            $t->id();
            $t->string('numero')->nullable()->unique();
            $t->string('patient_nom');
            $t->string('patient_telephone')->nullable();
            $t->decimal('total', 12, 2)->default(0);
            $t->foreignId('user_id')->constrained(); // celui qui a créé la facture
            $t->timestamps();
        });

        Schema::create('facture_lignes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('facture_id')->constrained()->cascadeOnDelete();
            $t->string('designation');
            $t->decimal('prix', 12, 2);
            $t->unsignedInteger('quantite')->default(1);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_lignes');
        Schema::dropIfExists('factures');
        Schema::dropIfExists('services');
    }
};
