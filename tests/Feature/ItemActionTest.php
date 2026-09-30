<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MposShift;
use App\Models\MposSalesH;
use App\Models\MposSalesD;
use App\Models\MposUser;
use App\Models\Muser;

class ItemActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = Muser::factory()->create();
        $this->mposUser = MposUser::create([
            'nid_user' => $this->user->id,
            'nid_outlet' => 1,
            'cname' => 'Test User',
            'fcashier' => true,
        ]);
        
        $this->shift = MposShift::create([
            'nid_outlet' => 1,
            'nid_user' => $this->mposUser->nid,
            'cshift_no' => 'SH-TEST-123',
            'dopened_at' => now(),
            'nopening_cash' => 0,
            'cstatus' => 'OPEN',
            'ncancellation_cash' => 0,
            'nrefund_cash' => 0,
        ]);
    }

    protected function createTransaction()
    {
        $trx = MposSalesH::create([
            'cnotransaction' => 'TRX-TEST-' . rand(100, 999),
            'dtransaction' => now(),
            'nid_outlet' => 1,
            'nid_user' => $this->mposUser->nid,
            'nid_shift' => $this->shift->nid,
            'cordertype' => 'DINE_IN',
            'nsubtotal' => 52000,
            'ngrandtotal' => 52000,
            'npaid' => 52000,
            'nitem' => 3,
            'cstatus' => 'PAID',
        ]);

        $d1 = MposSalesD::create(['nid_transaction' => $trx->nid, 'nid_product' => 1, 'cname' => 'Mix Platter', 'nqty' => 1, 'nprice' => 23000, 'nsubtotal' => 23000]);
        $d2 = MposSalesD::create(['nid_transaction' => $trx->nid, 'nid_product' => 2, 'cname' => 'French Fries', 'nqty' => 1, 'nprice' => 19000, 'nsubtotal' => 19000]);
        $d3 = MposSalesD::create(['nid_transaction' => $trx->nid, 'nid_product' => 3, 'cname' => 'Teh Ice', 'nqty' => 1, 'nprice' => 10000, 'nsubtotal' => 10000]);

        return [$trx, $d1, $d2, $d3];
    }
    
    public function test_void_partial()
    {
        [$trx, $d1, $d2, $d3] = $this->createTransaction();
        
        $response = $this->actingAs($this->user)->postJson("/api/pos/transactions/{$trx->nid}/items/void", [
            'items' => [
                ['detail_id' => $d1->nid, 'qty' => 1]
            ],
            'reason' => 'Test Void'
        ]);

        $response->assertStatus(200);
        
        $d1->refresh();
        $trx->refresh();
        $this->shift->refresh();
        
        $this->assertEquals(1, $d1->nqty_void);
        $this->assertEquals(0, $d1->nqty_refund);
        $this->assertEquals('PAID', $trx->cstatus);
        $this->assertEquals(23000, $this->shift->ncancellation_cash);
    }

    public function test_void_until_empty()
    {
        [$trx, $d1, $d2, $d3] = $this->createTransaction();
        
        $this->actingAs($this->user)->postJson("/api/pos/transactions/{$trx->nid}/items/void", [
            'items' => [
                ['detail_id' => $d1->nid, 'qty' => 1],
                ['detail_id' => $d2->nid, 'qty' => 1],
                ['detail_id' => $d3->nid, 'qty' => 1]
            ],
            'reason' => 'Test Void All'
        ]);

        $trx->refresh();
        $this->assertEquals('VOID', $trx->cstatus);
    }
    
    public function test_mixed_actions()
    {
        [$trx, $d1, $d2, $d3] = $this->createTransaction();
        
        $this->actingAs($this->user)->postJson("/api/pos/transactions/{$trx->nid}/items/void", [
            'items' => [
                ['detail_id' => $d1->nid, 'qty' => 1]
            ],
            'reason' => 'Test Void'
        ]);
        
        $this->actingAs($this->user)->postJson("/api/pos/transactions/{$trx->nid}/items/refund", [
            'items' => [
                ['detail_id' => $d2->nid, 'qty' => 1],
                ['detail_id' => $d3->nid, 'qty' => 1]
            ],
            'reason' => 'Test Refund'
        ]);

        $trx->refresh();
        // Should not be VOID or REFUND because it is mixed
        $this->assertEquals('PAID', $trx->cstatus);
    }
}
