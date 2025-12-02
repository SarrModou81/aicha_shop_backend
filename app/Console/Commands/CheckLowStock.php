<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Events\StockLow;
use Illuminate\Console\Command;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-low';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for low stock products and send alerts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for low stock products...');

        $lowStockProducts = Stock::whereRaw('quantity <= low_stock_threshold')
            ->with('produit.vendeur')
            ->get();

        if ($lowStockProducts->isEmpty()) {
            $this->info('No low stock products found.');
            return 0;
        }

        $count = 0;

        foreach ($lowStockProducts as $stock) {
            if ($stock->produit && $stock->produit->vendeur) {
                event(new StockLow($stock));
                $count++;
                $this->info("Alert sent for: {$stock->produit->name}");
            }
        }

        $this->info("Sent {$count} low stock alerts.");

        return 0;
    }
}
