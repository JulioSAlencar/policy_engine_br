<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    public function test_atributo_risk_color_mapeia_o_nivel_de_risco(): void
    {
        $this->assertSame('red',    (new AuditLog(['risk_level' => 'critical']))->risk_color);
        $this->assertSame('orange', (new AuditLog(['risk_level' => 'high']))->risk_color);
        $this->assertSame('yellow', (new AuditLog(['risk_level' => 'medium']))->risk_color);
        $this->assertSame('green',  (new AuditLog(['risk_level' => 'low']))->risk_color);
        $this->assertSame('gray',   (new AuditLog())->risk_color);
    }

    public function test_cast_booleano_de_has_sensitive_data(): void
    {
        $log = new AuditLog(['has_sensitive_data' => 1]);

        $this->assertTrue($log->has_sensitive_data);
        $this->assertIsBool($log->has_sensitive_data);
    }
}
