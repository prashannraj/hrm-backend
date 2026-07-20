<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fullName');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('companyName');
            $table->boolean('agreementSigned')->default(false);
            $table->timestamp('signatureDate')->nullable();
            $table->string('signatureName')->nullable();
            $table->string('signatureTitle')->nullable();
            $table->string('signatureType')->nullable(); // Upload, Draw, Type
            $table->text('signatureData')->nullable(); // Image URL/base64 or Typed Text
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. Employees Table
        Schema::create('employees', function (Blueprint $table) {
            $table->string('id')->primary(); // EMP-001, etc.
            $table->string('name');
            $table->string('gender');
            $table->date('dob');
            $table->string('maritalStatus');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('emergencyContact')->nullable();
            $table->string('citizenshipNo')->nullable();
            $table->string('passportNo')->nullable();
            $table->date('joinDate');
            $table->integer('probationMonths')->default(3);
            $table->string('contractType'); // Permanent, Contractual, etc.
            $table->string('department');
            $table->string('designation');
            $table->decimal('salaryBasic', 12, 2)->default(0.00);
            $table->decimal('salaryAllowances', 12, 2)->default(0.00);
            $table->decimal('salaryDeductions', 12, 2)->default(0.00);
            $table->string('pan')->nullable();
            $table->string('ssf')->nullable();
            $table->string('cit')->nullable();
            $table->string('taxInfo')->nullable();
            $table->json('assignedAssets')->nullable(); // JSON array of asset IDs
            $table->text('education')->nullable();
            $table->text('experience')->nullable();
            $table->text('dependents')->nullable();
            $table->string('profilePicture')->nullable();
            $table->json('documents')->nullable(); // JSON list of uploaded docs
            $table->json('allowancesList')->nullable(); // JSON compensation components
            $table->json('deductionsList')->nullable(); // JSON compensation components
            $table->json('lifecycleHistory')->nullable(); // JSON history entries
            $table->timestamps();
        });

        // 3. Attendance Logs Table
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->string('id')->primary(); // ATT-001
            $table->string('employeeId');
            $table->string('employeeName');
            $table->date('date');
            $table->string('checkIn');
            $table->string('checkOut')->nullable();
            $table->string('status'); // Present, Late, Absent, Half Day
            $table->integer('overtimeMinutes')->default(0);
            $table->integer('lateMinutes')->default(0);
            $table->timestamps();

            $table->foreign('employeeId')->references('id')->on('employees')->onDelete('cascade');
        });

        // 4. Leave Requests Table
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->string('id')->primary(); // LV-101
            $table->string('employeeId');
            $table->string('employeeName');
            $table->string('leaveType');
            $table->date('startDate');
            $table->date('endDate');
            $table->text('reason')->nullable();
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected
            $table->string('approvedBy')->nullable();
            $table->timestamps();

            $table->foreign('employeeId')->references('id')->on('employees')->onDelete('cascade');
        });

        // 5. WFH Requests Table
        Schema::create('wfh_requests', function (Blueprint $table) {
            $table->string('id')->primary(); // WFH-001
            $table->string('employeeId');
            $table->string('employeeName');
            $table->date('startDate');
            $table->date('endDate');
            $table->text('reason')->nullable();
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected
            $table->string('approvedBy')->nullable();
            $table->timestamps();

            $table->foreign('employeeId')->references('id')->on('employees')->onDelete('cascade');
        });

        // 6. Timesheets Table
        Schema::create('timesheets', function (Blueprint $table) {
            $table->string('id')->primary(); // TS-001
            $table->string('employeeId');
            $table->string('employeeName');
            $table->date('date');
            $table->text('task');
            $table->string('project');
            $table->decimal('hours', 4, 1)->default(8.0);
            $table->string('status')->default('Draft'); // Draft, Submitted, Approved
            $table->string('approvedBy')->nullable();
            $table->timestamps();

            $table->foreign('employeeId')->references('id')->on('employees')->onDelete('cascade');
        });

        // 7. Travel Requests Table
        Schema::create('travel_requests', function (Blueprint $table) {
            $table->string('id')->primary(); // TRV-001
            $table->string('employeeId');
            $table->string('employeeName');
            $table->string('destination');
            $table->text('purpose')->nullable();
            $table->date('startDate');
            $table->date('endDate');
            $table->decimal('estimatedCost', 12, 2)->default(0.00);
            $table->decimal('advanceAmount', 12, 2)->default(0.00);
            $table->string('status')->default('Pending'); // Pending, Approved, Settled, Rejected
            $table->json('expenses')->nullable(); // JSON list of settled items {item, amount}
            $table->string('approvedBy')->nullable();
            $table->timestamps();

            $table->foreign('employeeId')->references('id')->on('employees')->onDelete('cascade');
        });

        // 8. Assets Table
        Schema::create('assets', function (Blueprint $table) {
            $table->string('id')->primary(); // AST-001
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category'); // IT, Furniture, Vehicle, Equipment
            $table->string('assignedTo')->nullable();
            $table->date('purchaseDate');
            $table->decimal('cost', 12, 2)->default(0.00);
            $table->string('status')->default('Active'); // Active, Maintenance, Disposed
            $table->json('maintenanceLogs')->nullable(); // JSON logs [{date, cost, description}]
            $table->timestamps();

            $table->foreign('assignedTo')->references('id')->on('employees')->onDelete('set null');
        });

        // 9. Vehicles Table (Fleet)
        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('id')->primary(); // VEH-001
            $table->string('plateNumber')->unique();
            $table->string('model');
            $table->string('driverName');
            $table->string('status')->default('Available'); // Available, In Trip, Maintenance
            $table->json('fuelLogs')->nullable(); // JSON fuel records
            $table->json('trips')->nullable(); // JSON travel loops
            $table->timestamps();
        });

        // 10. Audit Logs Table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('timestamp')->useCurrent();
            $table->string('user');
            $table->text('action');
            $table->string('module');
            $table->timestamps();
        });

        // 11. Policies Table
        Schema::create('policies', function (Blueprint $table) {
            $table->string('id')->primary(); // POL-001
            $table->string('title');
            $table->string('category'); // HR, Finance, IT, Operations
            $table->string('version')->default('v1.0');
            $table->date('publishDate');
            $table->text('content');
            $table->json('acknowledgedBy')->nullable(); // JSON array of employee IDs
            $table->timestamps();
        });

        // 12. Organization Settings Table
        Schema::create('organization_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('acronym')->nullable();
            $table->string('registeredAddress')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('registrationNo')->nullable();
            $table->string('fiscalYear')->nullable();
            $table->json('departments')->nullable();
            $table->json('designations')->nullable();
            $table->json('leavePolicies')->nullable();
            $table->longText('logoUrl')->nullable();
            $table->longText('logoThumbUrl')->nullable();
            $table->json('ssoConfig')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('organization_settings');
        Schema::dropIfExists('policies');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('travel_requests');
        Schema::dropIfExists('timesheets');
        Schema::dropIfExists('wfh_requests');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('users');

        Schema::enableForeignKeyConstraints();
    }
};
