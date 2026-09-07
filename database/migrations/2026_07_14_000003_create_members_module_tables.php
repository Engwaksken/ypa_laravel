<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the Members module tables that already exist in the production
 * `kemmytec_ypa` database. This migration is recorded as applied in the
 * `migrations` table, so `php artisan migrate` will skip it against
 * production. It exists so the sqlite :memory: test database can build the
 * same shape. No foreign keys are added (production data may not satisfy
 * them); only indexes are reproduced.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->string('membership_id')->unique();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('other_name')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->enum('sex', ['Male', 'Female', 'Other'])->nullable();
                $table->string('gender')->nullable();
                $table->string('nin')->nullable()->unique();
                $table->string('tin_number')->nullable();
                $table->string('tin')->nullable();
                $table->string('tax_identification_number')->nullable();
                $table->string('nationality')->nullable();
                $table->text('address')->nullable();
                $table->enum('region', ['Central', 'Eastern', 'Western', 'Northern'])->nullable();
                $table->string('district_residence')->nullable();
                $table->string('district')->nullable();
                $table->enum('employment_status', [
                    'Employed (Full-time)',
                    'Employed (Part-time)',
                    'Self-employed',
                    'Casual / Temporary worker',
                    'Contract employee',
                    'Unemployed',
                    'Student',
                    'Retired',
                    'Farmer / Agribusiness operator',
                    'Business owner / Entrepreneur',
                    'Informal sector worker',
                    'Other',
                ])->nullable();
                $table->string('employment_other')->nullable();
                $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Separated'])->nullable();
                $table->integer('children_count')->nullable()->default(0);
                $table->enum('source', ['Radio', 'TV', 'Social Media', 'YPA Website', 'Outreaches', 'Exhibitions', 'Personal', 'Referral', 'Other'])->nullable();
                $table->string('source_type')->nullable();
                $table->string('source_station')->nullable();
                $table->string('radio_station')->nullable();
                $table->string('tv_station')->nullable();
                $table->string('source_other')->nullable();
                $table->string('email')->nullable();
                $table->string('telephone1')->nullable();
                $table->string('telephone2')->nullable();
                $table->string('mother_name')->nullable();
                $table->string('mother_phone')->nullable();
                $table->string('father_name')->nullable();
                $table->string('father_phone')->nullable();
                $table->enum('account_type', ['Personal', 'Joint', 'Group', 'Infant'])->nullable();
                $table->string('bank_account')->nullable();
                $table->string('bank_account_name')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_branch')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('mobilizer_id')->nullable()->index();
                $table->enum('membership_status', ['Active', 'Pending', 'Suspended', 'Expired', 'Inactive'])->nullable()->default('Pending');
                $table->decimal('membership_fee_amount', 15, 2)->nullable();
                $table->decimal('membership_fee_paid', 15, 2)->nullable();
                $table->decimal('membership_outstanding', 15, 2)->nullable();
                $table->string('membership_payment_method')->nullable();
                $table->string('membership_payment_reference')->nullable();
                $table->string('membership_receipt_number')->nullable();
                $table->string('membership_payment_status')->nullable();
                $table->dateTime('membership_paid_at')->nullable();
                $table->unsignedBigInteger('membership_payment_transaction_id')->nullable();
                $table->string('member_photo')->nullable();
                $table->string('photo')->nullable();
                $table->string('image_url')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('member_next_of_kin')) {
            Schema::create('member_next_of_kin', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('member_id')->index();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('nin')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->date('dob')->nullable();
                $table->string('relationship')->nullable();
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('telephone1')->nullable();
                $table->string('telephone2')->nullable();
                $table->string('email')->nullable();
                $table->string('photo')->nullable();
                $table->string('nok_photo')->nullable();
                $table->string('photo_path')->nullable();
                $table->string('image_url')->nullable();
                $table->string('country')->nullable();
                $table->string('region')->nullable();
                $table->string('district_residence')->nullable();
                $table->string('district')->nullable();
                $table->string('subcounty')->nullable();
                $table->string('parish')->nullable();
                $table->string('village')->nullable();
                $table->string('occupation')->nullable();
                $table->string('gender')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('member_bank_details')) {
            Schema::create('member_bank_details', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('member_id')->unique();
                $table->string('bank_account')->nullable();
                $table->string('account_name')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_branch')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('member_dependents')) {
            Schema::create('member_dependents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('member_id')->index();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('relationship')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('gender')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mobilizers')) {
            Schema::create('mobilizers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('department')->nullable();
                $table->string('position')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('email')->nullable();
                $table->string('branch_region')->nullable()->index();
                $table->string('supervisor')->nullable();
                $table->string('status')->nullable()->default('Active');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('member_mobilization')) {
            Schema::create('member_mobilization', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('mobilizer_id')->index();
                $table->date('mobilized_date')->nullable();
                $table->string('channel')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['member_id', 'mobilizer_id', 'mobilized_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_mobilization');
        Schema::dropIfExists('mobilizers');
        Schema::dropIfExists('member_dependents');
        Schema::dropIfExists('member_bank_details');
        Schema::dropIfExists('member_next_of_kin');
        Schema::dropIfExists('members');
    }
};
