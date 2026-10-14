<?php

namespace Tests\Unit;

use App\Services\PermissionService;
use App\Services\TableExportService;
use Tests\TestCase;

class TableExportServiceTest extends TestCase
{
    public function test_separate_export_permissions_are_not_bypassed_by_shared_table_controls(): void
    {
        $this->mock(PermissionService::class, function ($mock): void {
            $mock->shouldReceive('canAny')->with(['payments_export'])->once()->andReturn(false);
            $mock->shouldReceive('canAny')->with(['export_sales_report'])->once()->andReturn(false);
            $mock->shouldReceive('canAny')->with(['members_export'])->once()->andReturn(true);
        });
        $service = new TableExportService;
        $this->assertFalse($service->allowed('payments.index'));
        $this->assertFalse($service->allowed('sales-reports.index'));
        $this->assertTrue($service->allowed('members.index'));
        $this->assertTrue($service->allowed('contracts.show'));
    }
}
