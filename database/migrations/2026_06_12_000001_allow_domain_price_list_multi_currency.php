<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('domain_price_list')) {
            return;
        }

        $oldUniqueName = $this->findUniqueIndex(['tld'], 'domain_price_list_tld_unique');
        $hasCompositeUnique = $this->findUniqueIndex(['tld', 'currency'], 'domain_price_list_tld_currency_unique') !== null;

        if ($oldUniqueName !== null) {
            Schema::table('domain_price_list', function (Blueprint $table) use ($oldUniqueName): void {
                $table->dropUnique($oldUniqueName);
            });
        }

        if (! $hasCompositeUnique) {
            Schema::table('domain_price_list', function (Blueprint $table): void {
                $table->unique(['tld', 'currency'], 'domain_price_list_tld_currency_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('domain_price_list')) {
            return;
        }

        $compositeUniqueName = $this->findUniqueIndex(['tld', 'currency'], 'domain_price_list_tld_currency_unique');
        $hasTldUnique = $this->findUniqueIndex(['tld'], 'domain_price_list_tld_unique') !== null;

        if ($compositeUniqueName !== null) {
            Schema::table('domain_price_list', function (Blueprint $table) use ($compositeUniqueName): void {
                $table->dropUnique($compositeUniqueName);
            });
        }

        if (! $hasTldUnique) {
            Schema::table('domain_price_list', function (Blueprint $table): void {
                $table->unique('tld', 'domain_price_list_tld_unique');
            });
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function findUniqueIndex(array $columns, string $preferredName): ?string
    {
        $expectedColumns = array_map('strtolower', $columns);

        foreach (Schema::getIndexes('domain_price_list') as $index) {
            $name = (string) ($index['name'] ?? '');
            $indexColumns = array_map('strtolower', $index['columns'] ?? []);
            $isUnique = (bool) ($index['unique'] ?? false);

            if ($name === $preferredName) {
                return $name;
            }

            if ($isUnique && $indexColumns === $expectedColumns) {
                return $name;
            }
        }

        return null;
    }
};
