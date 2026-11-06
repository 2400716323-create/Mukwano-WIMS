<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\BinInventory;
use App\Models\Category;
use App\Models\InventorySetting;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedUsers();
            $this->seedSettings();
            $this->seedSuppliers();
            $this->seedCategoriesAndProducts();
            $this->seedInventoryAndMovements();
        });

        $this->command?->info('Demo users, categories, products, suppliers, and stock data seeded successfully.');
    }

    private function seedUsers(): void
    {
        $defaultPassword = env('DEMO_USER_PASSWORD', 'password');

        $users = [
            [
                'name' => 'Mukwano Administrator',
                'email' => env('DEMO_USER_EMAIL', 'admin@mukwano.com'),
                'role' => User::ROLE_ADMINISTRATOR,
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Sarah Namubiru (Warehouse Manager)',
                'email' => 'manager@mukwano.com',
                'role' => User::ROLE_WAREHOUSE_MANAGER,
                'password' => $defaultPassword,
            ],
            [
                'name' => 'John Okello (Storekeeper)',
                'email' => 'storekeeper@mukwano.com',
                'role' => User::ROLE_STOREKEEPER,
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Grace Atuhaire (Procurement Officer)',
                'email' => 'procurement@mukwano.com',
                'role' => User::ROLE_PROCUREMENT_OFFICER,
                'password' => $defaultPassword,
            ],
            [
                'name' => 'David Kigozi (Dispatch Officer)',
                'email' => 'dispatch@mukwano.com',
                'role' => User::ROLE_DISPATCH_OFFICER,
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Christine Achieng (Auditor)',
                'email' => 'auditor@mukwano.com',
                'role' => User::ROLE_AUDITOR,
                'password' => $defaultPassword,
            ],
        ];

        foreach ($users as $data) {
            User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                    'role' => $data['role'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedSettings(): void
    {
        $admin = User::query()->where('role', User::ROLE_ADMINISTRATOR)->first();

        InventorySetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Mukwano Industries Uganda Limited',
                'accuracy_tolerance_percent' => '2.000',
                'variance_threshold_percent' => '5.000',
                'expiry_windows' => [
                    'soon' => 30,
                    'warning' => 60,
                    'notice' => 90,
                ],
                'updated_by' => $admin?->id,
            ]
        );
    }

    private function seedSuppliers(): void
    {
        $suppliers = [
            [
                'name' => 'Palm Oil Refineries East Africa',
                'code' => 'SUP-PO-001',
                'contact_person' => 'Emmanuel Mukasa',
                'phone' => '+256 772 100 200',
                'email' => 'supplies@palmoil-ea.com',
                'address' => 'Plot 12 Jinja Road, Jinja Industrial Area',
                'notes' => 'Primary crude & refined palm oil supplier',
                'status' => 'active',
            ],
            [
                'name' => 'AfriPack Packaging Solutions',
                'code' => 'SUP-PKG-002',
                'contact_person' => 'Harriet Nakato',
                'phone' => '+256 701 450 780',
                'email' => 'orders@afripack.co.ug',
                'address' => 'Plot 88 Kyambogo Industrial Estate, Kampala',
                'notes' => 'Corrugated boxes and HDPE containers',
                'status' => 'active',
            ],
            [
                'name' => 'Uganda Industrial Chemicals Ltd',
                'code' => 'SUP-CHM-003',
                'contact_person' => 'Dr. Patrick Byaruhanga',
                'phone' => '+256 752 900 330',
                'email' => 'sales@ugandachemicals.com',
                'address' => 'Plot 5 Kawempe Commercial Zone, Kampala',
                'notes' => 'Sodium hydroxide, silicates, and fragrance essences',
                'status' => 'active',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->updateOrCreate(['code' => $supplier['code']], $supplier);
        }
    }

    private function seedCategoriesAndProducts(): void
    {
        $categories = [
            [
                'name' => 'Edible Oils & Fats',
                'description' => 'Cooking oils, blended vegetable oils, and margarine products.',
                'products' => [
                    [
                        'sku' => 'OIL-ROKI-5L',
                        'sap_material_number' => 'SAP-100201',
                        'name' => 'Roki Fortified Cooking Oil 5L',
                        'description' => 'Triple refined, Vitamin A & D fortified edible vegetable cooking oil.',
                        'unit' => 'jerrican',
                        'min_level' => '100.000',
                        'reorder_level' => '300.000',
                        'max_level' => '1500.000',
                    ],
                    [
                        'sku' => 'OIL-MKW-20L',
                        'sap_material_number' => 'SAP-100202',
                        'name' => 'Mukwano Cooking Oil 20L Jerrycan',
                        'description' => 'Bulk refined cooking oil for commercial and institutional catering.',
                        'unit' => 'jerrycan',
                        'min_level' => '50.000',
                        'reorder_level' => '150.000',
                        'max_level' => '800.000',
                    ],
                    [
                        'sku' => 'FAT-KYEYO-500G',
                        'sap_material_number' => 'SAP-100203',
                        'name' => 'Kyeyo Bakery Margarine 500g',
                        'description' => 'High-grade baker margarine for confectionery and home baking.',
                        'unit' => 'tub',
                        'min_level' => '200.000',
                        'reorder_level' => '500.000',
                        'max_level' => '3000.000',
                    ],
                ],
            ],
            [
                'name' => 'Soaps & Detergents',
                'description' => 'Laundry bar soaps, toilet soaps, and powder detergents.',
                'products' => [
                    [
                        'sku' => 'SOAP-WSTAR-1KG',
                        'sap_material_number' => 'SAP-200301',
                        'name' => 'White Star Laundry Bar Soap 1kg',
                        'description' => 'Long-lasting multi-purpose household laundry and dishwashing soap bar.',
                        'unit' => 'bar',
                        'min_level' => '500.000',
                        'reorder_level' => '1500.000',
                        'max_level' => '10000.000',
                    ],
                    [
                        'sku' => 'SOAP-NYOTA-800G',
                        'sap_material_number' => 'SAP-200302',
                        'name' => 'Chapa Nyota Yellow Soap 800g',
                        'description' => 'Standard cleansing and laundry yellow soap bar with coconut extract.',
                        'unit' => 'bar',
                        'min_level' => '400.000',
                        'reorder_level' => '1000.000',
                        'max_level' => '8000.000',
                    ],
                    [
                        'sku' => 'DET-MAGIC-1KG',
                        'sap_material_number' => 'SAP-200303',
                        'name' => 'Star Magic Washing Powder 1kg',
                        'description' => 'Active enzyme stain-remover detergent powder with lemon fragrance.',
                        'unit' => 'pouch',
                        'min_level' => '150.000',
                        'reorder_level' => '400.000',
                        'max_level' => '2500.000',
                    ],
                ],
            ],
            [
                'name' => 'Plastics & Household',
                'description' => 'Industrial and domestic durable plastic containers and basins.',
                'products' => [
                    [
                        'sku' => 'PLAS-BSN-20L',
                        'sap_material_number' => 'SAP-300401',
                        'name' => 'Mukwano Heavy Duty Basin 20L',
                        'description' => 'Virgin polypropylene multi-color wash basin with reinforced rim.',
                        'unit' => 'piece',
                        'min_level' => '80.000',
                        'reorder_level' => '200.000',
                        'max_level' => '1200.000',
                    ],
                    [
                        'sku' => 'PLAS-JCAN-20L',
                        'sap_material_number' => 'SAP-300402',
                        'name' => 'Mukwano Water Jerrycan 20L (Blue)',
                        'description' => 'High-density polyethylene food-grade water and liquid storage jerrycan.',
                        'unit' => 'piece',
                        'min_level' => '100.000',
                        'reorder_level' => '250.000',
                        'max_level' => '1500.000',
                    ],
                ],
            ],
            [
                'name' => 'Personal Care & Cosmetics',
                'description' => 'Petroleum jellies, glycerines, and skin conditioning products.',
                'products' => [
                    [
                        'sku' => 'PC-ROYAL-250G',
                        'sap_material_number' => 'SAP-400501',
                        'name' => 'Royal Care Pure Petroleum Jelly 250g',
                        'description' => 'Triple-purified perfumed skin moisturising and protecting jelly.',
                        'unit' => 'jar',
                        'min_level' => '250.000',
                        'reorder_level' => '600.000',
                        'max_level' => '4000.000',
                    ],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $category = Category::query()->updateOrCreate(
                ['name' => $catData['name']],
                [
                    'description' => $catData['description'],
                    'status' => 'active',
                ]
            );

            foreach ($catData['products'] as $prodData) {
                Product::query()->updateOrCreate(
                    ['sku' => $prodData['sku']],
                    array_merge($prodData, [
                        'category_id' => $category->id,
                        'status' => 'active',
                    ])
                );
            }
        }
    }

    private function seedInventoryAndMovements(): void
    {
        $bins = Location::query()->where('type', 'bin')->where('status', 'active')->get();
        if ($bins->isEmpty()) {
            return;
        }

        $admin = User::query()->where('role', User::ROLE_ADMINISTRATOR)->first();
        $products = Product::query()->get();

        $batchesData = [
            [
                'sku' => 'OIL-ROKI-5L',
                'batch_number' => 'ROKI-2026-B01',
                'manufactured_at' => today()->subMonths(2),
                'expires_at' => today()->addMonths(10),
                'quantity' => '280.000',
                'bin_index' => 0,
            ],
            [
                'sku' => 'OIL-ROKI-5L',
                'batch_number' => 'ROKI-2026-B02',
                'manufactured_at' => today()->subMonth(),
                'expires_at' => today()->addMonths(11),
                'quantity' => '150.000',
                'bin_index' => 1,
            ],
            [
                'sku' => 'OIL-MKW-20L',
                'batch_number' => 'MKW20-2026-001',
                'manufactured_at' => today()->subMonths(3),
                'expires_at' => today()->addMonths(9),
                'quantity' => '85.000',
                'bin_index' => 2,
            ],
            [
                'sku' => 'SOAP-WSTAR-1KG',
                'batch_number' => 'WSTAR-LOT-441',
                'manufactured_at' => today()->subMonths(4),
                'expires_at' => today()->addDays(25), // Near expiry alert!
                'quantity' => '1200.000',
                'bin_index' => 3,
            ],
            [
                'sku' => 'SOAP-NYOTA-800G',
                'batch_number' => 'NYOTA-LOT-108',
                'manufactured_at' => today()->subYear(),
                'expires_at' => today()->subDays(10), // Expired!
                'quantity' => '320.000',
                'bin_index' => 4,
            ],
            [
                'sku' => 'DET-MAGIC-1KG',
                'batch_number' => 'MAGIC-2026-X1',
                'manufactured_at' => today()->subMonths(1),
                'expires_at' => today()->addMonths(18),
                'quantity' => '450.000',
                'bin_index' => 5,
            ],
            [
                'sku' => 'PLAS-BSN-20L',
                'batch_number' => 'BSN-LOT-099',
                'manufactured_at' => today()->subMonths(2),
                'expires_at' => null, // Indefinite
                'quantity' => '160.000',
                'bin_index' => 0,
            ],
            [
                'sku' => 'PC-ROYAL-250G',
                'batch_number' => 'ROYAL-2026-C1',
                'manufactured_at' => today()->subMonths(2),
                'expires_at' => today()->addMonths(22),
                'quantity' => '540.000',
                'bin_index' => 1,
            ],
        ];

        foreach ($batchesData as $item) {
            $product = $products->firstWhere('sku', $item['sku']);
            if (! $product) {
                continue;
            }

            $bin = $bins->get($item['bin_index'] % $bins->count());
            if (! $bin) {
                continue;
            }

            $batch = Batch::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'batch_number' => $item['batch_number'],
                ],
                [
                    'manufactured_at' => $item['manufactured_at'],
                    'expires_at' => $item['expires_at'],
                ]
            );

            // Stock Balance
            StockBalance::query()->updateOrCreate(
                [
                    'batch_id' => $batch->id,
                    'location_id' => $bin->id,
                ],
                [
                    'quantity' => $item['quantity'],
                ]
            );

            // Bin Inventory (Dual Ledger)
            BinInventory::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'location_id' => $bin->id,
                    'batch_number' => $batch->batch_number,
                ],
                [
                    'quantity' => $item['quantity'],
                ]
            );

            // Record initial movement if missing
            $exists = StockMovement::query()
                ->where('batch_id', $batch->id)
                ->where('to_location_id', $bin->id)
                ->exists();

            if (! $exists) {
                StockMovement::query()->create([
                    'batch_id' => $batch->id,
                    'from_location_id' => null,
                    'to_location_id' => $bin->id,
                    'quantity' => $item['quantity'],
                    'movement_type' => 'receipt',
                    'reason' => 'Initial warehouse stock onboarding for Mukwano demo',
                    'performed_by' => $admin?->id,
                    'moved_at' => now()->subDays(5),
                ]);
            }
        }
    }
}
