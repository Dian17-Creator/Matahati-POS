<?php

namespace Tests\Unit;

use App\Models\MposPurchaseD;
use App\Models\MposStockMutation;
use App\Models\MposStockOpnameD;
use Tests\TestCase;

class InventoryItemTypeTest extends TestCase
{
    public function test_stock_mutation_normalizes_all_three_product_types(): void
    {
        $this->assertSame('PRODUK', MposStockMutation::normalizeItemType('PRODUK'));
        $this->assertSame('PRODUK', MposStockMutation::normalizeItemType('PRODUCT'));
        $this->assertSame('SETENGAH_JADI', MposStockMutation::normalizeItemType('SETENGAH_JADI'));
        $this->assertSame('BAHAN_BAKU', MposStockMutation::normalizeItemType('BAHAN_BAKU'));
        $this->assertSame('BAHAN_BAKU', MposStockMutation::normalizeItemType('INGREDIENT'));
    }

    public function test_bahan_baku_stored_in_mpos_product_is_not_treated_as_legacy_ingredient(): void
    {
        $mutation = new MposStockMutation([
            'citem_type' => MposStockMutation::ITEM_BAHAN_BAKU,
            'nid_product' => 42,
            'nid_ingredient' => null,
        ]);

        $this->assertFalse($mutation->isIngredient());
        $this->assertSame('BAHAN_BAKU', $mutation->getItemTypeNormalized());
        $this->assertSame('Bahan Baku', $mutation->getItemTypeLabel());

        $setengahJadi = new MposStockMutation([
            'citem_type' => MposStockMutation::ITEM_SETENGAH_JADI,
            'nid_product' => 43,
            'nid_ingredient' => null,
        ]);

        $this->assertFalse($setengahJadi->isIngredient());
        $this->assertSame('SETENGAH_JADI', $setengahJadi->getItemTypeNormalized());
        $this->assertSame('Setengah Jadi', $setengahJadi->getItemTypeLabel());
    }

    public function test_legacy_ingredient_row_remains_backward_compatible(): void
    {
        $legacy = new MposStockMutation([
            'citem_type' => MposStockMutation::ITEM_INGREDIENT,
            'nid_product' => null,
            'nid_ingredient' => 7,
        ]);

        $this->assertTrue($legacy->isIngredient());
        $this->assertSame('BAHAN_BAKU', $legacy->getItemTypeNormalized());
        $this->assertSame('Bahan Baku', $legacy->getItemTypeLabel());
    }

    public function test_purchase_and_opname_detail_treat_mpos_product_bahan_baku_as_product_record(): void
    {
        $purchaseDetail = new MposPurchaseD([
            'citem_type' => MposPurchaseD::TYPE_BAHAN_BAKU,
            'nid_product' => 42,
            'nid_ingredient' => null,
        ]);

        $this->assertFalse($purchaseDetail->isIngredient());
        $this->assertSame('BAHAN_BAKU', $purchaseDetail->getItemTypeNormalized());
        $this->assertSame('Bahan Baku', $purchaseDetail->getItemTypeLabel());

        $opnameDetail = new MposStockOpnameD([
            'citem_type' => MposStockOpnameD::TYPE_SETENGAH_JADI,
            'nid_product' => 43,
            'nid_ingredient' => null,
        ]);

        $this->assertFalse($opnameDetail->isIngredient());
        $this->assertSame('SETENGAH_JADI', $opnameDetail->getItemTypeNormalized());
        $this->assertSame('Setengah Jadi', $opnameDetail->getItemTypeLabel());
    }
}

