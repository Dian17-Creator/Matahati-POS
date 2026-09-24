<?php

namespace Tests\Feature;

use App\Models\MposCashMovement;
use App\Models\MposSalesH;
use App\Models\MposShift;
use App\Models\MposUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShiftFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $mposUser;

    protected $outletId;

    protected $paymentId;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user biasa (atau muser jika tabel berbeda, di sini asumsikan User terhubung ke Sanctum)
        $this->user = User::factory()->create();

        // Setup outlet (tabel mpos_outlet memiliki nid, nid_dept, cname)
        // Kita bypass insert langsung jika model tidak memiliki factory
        $this->outletId = DB::table('mpos_outlet')->insertGetId([
            'nid_dept' => 1,
            'cname' => 'Test Outlet',
        ]);

        // Setup mpos_user
        $mposUserId = DB::table('mpos_user')->insertGetId([
            'nid_user' => $this->user->id,
            'nid_outlet' => $this->outletId,
            'fowner' => 1,
            'fcashier' => 1,
            'fcaptain' => 1,
        ]);
        $this->mposUser = MposUser::find($mposUserId);

        // Setup payment method CASH
        $this->paymentId = DB::table('mpos_payment')->insertGetId([
            'cname' => 'CASH',
        ]);
    }

    public function test_can_start_shift()
    {
        $response = $this->actingAs($this->user)->postJson('/api/shifts/start', [
            'nid_outlet' => $this->outletId,
            'nopening_cash' => 500000,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('mpos_shift', [
            'nid_outlet' => $this->outletId,
            'nid_user' => $this->mposUser->nid,
            'cstatus' => 'OPEN',
            'nopening_cash' => 500000,
        ]);
    }

    public function test_cannot_start_shift_if_already_open()
    {
        MposShift::create([
            'nid_outlet' => $this->outletId,
            'nid_user' => $this->mposUser->nid,
            'cshift_no' => 'SH-TEST-0001',
            'dopened_at' => now(),
            'nopening_cash' => 100000,
            'cstatus' => 'OPEN',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/shifts/start', [
            'nid_outlet' => $this->outletId,
            'nopening_cash' => 500000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_get_current_shift()
    {
        $shift = MposShift::create([
            'nid_outlet' => $this->outletId,
            'nid_user' => $this->mposUser->nid,
            'cshift_no' => 'SH-TEST-0001',
            'dopened_at' => now(),
            'nopening_cash' => 100000,
            'cstatus' => 'OPEN',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/shifts/current?nid_outlet='.$this->outletId);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cshift_no', 'SH-TEST-0001');
    }

    public function test_can_record_cash_in_out()
    {
        $shift = MposShift::create([
            'nid_outlet' => $this->outletId,
            'nid_user' => $this->mposUser->nid,
            'cshift_no' => 'SH-TEST-0001',
            'dopened_at' => now(),
            'nopening_cash' => 100000,
            'cstatus' => 'OPEN',
        ]);

        // CASH IN
        $this->actingAs($this->user)->postJson('/api/shifts/cash-movement', [
            'nid_shift' => $shift->nid,
            'ctype' => 'CASH_IN',
            'namount' => 50000,
            'cdescription' => 'Modal tambahan',
        ])->assertStatus(201);

        // CASH OUT
        $this->actingAs($this->user)->postJson('/api/shifts/cash-movement', [
            'nid_shift' => $shift->nid,
            'ctype' => 'CASH_OUT',
            'namount' => 20000,
        ])->assertStatus(201);

        $this->assertDatabaseHas('mpos_cash_movement', [
            'nid_shift' => $shift->nid,
            'ctype' => 'CASH_IN',
            'namount' => 50000,
        ]);

        $this->assertDatabaseHas('mpos_cash_movement', [
            'nid_shift' => $shift->nid,
            'ctype' => 'CASH_OUT',
            'namount' => 20000,
        ]);
    }

    public function test_can_close_shift_and_calculate_expected_cash()
    {
        $shift = MposShift::create([
            'nid_outlet' => $this->outletId,
            'nid_user' => $this->mposUser->nid,
            'cshift_no' => 'SH-TEST-0001',
            'dopened_at' => now(),
            'nopening_cash' => 100000,
            'cstatus' => 'OPEN',
        ]);

        // Tambah Cash In 50000, Cash Out 20000
        MposCashMovement::create(['nid_shift' => $shift->nid, 'nid_user' => $this->mposUser->nid, 'ctype' => 'CASH_IN', 'namount' => 50000, 'dcreated_at' => now()]);
        MposCashMovement::create(['nid_shift' => $shift->nid, 'nid_user' => $this->mposUser->nid, 'ctype' => 'CASH_OUT', 'namount' => 20000, 'dcreated_at' => now()]);

        // Tambah transaksi Cash Sales: 300000
        MposSalesH::create([
            'cnotransaction' => 'TRX-001',
            'dtransaction' => now(),
            'nid_user' => $this->mposUser->nid,
            'nid_shift' => $shift->nid,
            'nid_outlet' => $this->outletId,
            'nid_payment' => $this->paymentId,
            'cordertype' => MposSalesH::ORDER_TYPE_DINE_IN,
            'nsubtotal' => 300000,
            'ndiscount' => 0,
            'ntax' => 0,
            'ngrandtotal' => 300000,
            'npaid' => 300000,
            'nchange' => 0,
            'nitem' => 1,
            'cstatus' => MposSalesH::STATUS_PAID,
            'dcreated' => now(),
        ]);

        // Expected Cash = 100000 (opening) + 300000 (sales) + 50000 (in) - 20000 (out) = 430000
        // Actual Cash = 425000
        // Difference = -5000

        $response = $this->actingAs($this->user)->postJson('/api/shifts/'.$shift->nid.'/close', [
            'nactual_cash' => 425000,
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('mpos_shift', [
            'nid' => $shift->nid,
            'cstatus' => 'CLOSED',
            'nexpected_cash' => 430000,
            'nactual_cash' => 425000,
            'ndifference' => -5000,
        ]);
    }
}
