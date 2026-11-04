<?php

namespace App\Support\Finance;

class DefaultUkChartAccounts
{
    /** @return list<array{code: string, name: array{en: string, pl: string, pt: string}, type: string, subtype: string, normal_balance: string, parent_code: string|null, is_postable: bool}> */
    public static function all(): array
    {
        return [
            self::account('1000', 'Assets', 'Aktywa', 'Ativos', 'asset', 'heading', 'debit', null, false),
            self::account('1100', 'Bank and cash', 'Bank i kasa', 'Banco e caixa', 'asset', 'cash', 'debit', '1000'),
            self::account('1200', 'Trade debtors', 'Należności handlowe', 'Clientes', 'asset', 'accounts_receivable', 'debit', '1000'),
            self::account('1300', 'VAT receivable', 'VAT do odzyskania', 'IVA a recuperar', 'asset', 'tax_receivable', 'debit', '1000'),
            self::account('1400', 'Prepayments', 'Rozliczenia międzyokresowe', 'Pagamentos antecipados', 'asset', 'prepayment', 'debit', '1000'),
            self::account('1500', 'Equipment', 'Wyposażenie', 'Equipamento', 'asset', 'fixed_asset', 'debit', '1000'),
            self::account('1600', 'Accumulated depreciation', 'Umorzenie', 'Depreciação acumulada', 'asset', 'contra_asset', 'credit', '1000'),
            self::account('2000', 'Liabilities', 'Zobowiązania', 'Passivos', 'liability', 'heading', 'credit', null, false),
            self::account('2100', 'Trade creditors', 'Zobowiązania handlowe', 'Fornecedores', 'liability', 'accounts_payable', 'credit', '2000'),
            self::account('2200', 'VAT payable', 'VAT należny', 'IVA a pagar', 'liability', 'tax_payable', 'credit', '2000'),
            self::account('2300', 'PAYE and NIC payable', 'PAYE i NIC do zapłaty', 'PAYE e NIC a pagar', 'liability', 'payroll_tax', 'credit', '2000'),
            self::account('2400', 'Corporation tax payable', 'Podatek dochodowy do zapłaty', 'Imposto sobre sociedades a pagar', 'liability', 'corporation_tax', 'credit', '2000'),
            self::account('3000', 'Equity', 'Kapitał własny', 'Capital próprio', 'equity', 'heading', 'credit', null, false),
            self::account('3100', 'Share capital', 'Kapitał zakładowy', 'Capital social', 'equity', 'share_capital', 'credit', '3000'),
            self::account('3200', 'Retained earnings', 'Zyski zatrzymane', 'Resultados transitados', 'equity', 'retained_earnings', 'credit', '3000'),
            self::account('4000', 'Income', 'Przychody', 'Rendimentos', 'income', 'heading', 'credit', null, false),
            self::account('4100', 'Sales', 'Sprzedaż', 'Vendas', 'income', 'sales', 'credit', '4000'),
            self::account('4200', 'Other income', 'Pozostałe przychody', 'Outros rendimentos', 'income', 'other_income', 'credit', '4000'),
            self::account('4300', 'Foreign exchange gains', 'Dodatnie różnice kursowe', 'Ganhos cambiais', 'income', 'fx_gain', 'credit', '4000'),
            self::account('5000', 'Expenses', 'Koszty', 'Gastos', 'expense', 'heading', 'debit', null, false),
            self::account('5100', 'Cost of sales', 'Koszt własny sprzedaży', 'Custo das vendas', 'expense', 'cost_of_sales', 'debit', '5000'),
            self::account('6000', 'Wages and salaries', 'Wynagrodzenia', 'Salários', 'expense', 'payroll', 'debit', '5000'),
            self::account('6100', 'Rent and rates', 'Czynsz i opłaty', 'Rendas e taxas', 'expense', 'rent', 'debit', '5000'),
            self::account('6200', 'Software and subscriptions', 'Oprogramowanie i subskrypcje', 'Software e subscrições', 'expense', 'software', 'debit', '5000'),
            self::account('6300', 'Office costs', 'Koszty biurowe', 'Custos de escritório', 'expense', 'office', 'debit', '5000'),
            self::account('6400', 'Travel and subsistence', 'Podróże i diety', 'Viagens e estadias', 'expense', 'travel', 'debit', '5000'),
            self::account('6500', 'Professional fees', 'Usługi profesjonalne', 'Honorários profissionais', 'expense', 'professional_fees', 'debit', '5000'),
            self::account('6600', 'Advertising and marketing', 'Reklama i marketing', 'Publicidade e marketing', 'expense', 'marketing', 'debit', '5000'),
            self::account('6700', 'Utilities and communications', 'Media i komunikacja', 'Serviços e comunicações', 'expense', 'utilities', 'debit', '5000'),
            self::account('6800', 'Insurance', 'Ubezpieczenia', 'Seguros', 'expense', 'insurance', 'debit', '5000'),
            self::account('6900', 'Bank charges', 'Opłaty bankowe', 'Encargos bancários', 'expense', 'bank_fees', 'debit', '5000'),
            self::account('7000', 'Depreciation', 'Amortyzacja', 'Depreciação', 'expense', 'depreciation', 'debit', '5000'),
            self::account('7100', 'Foreign exchange losses', 'Ujemne różnice kursowe', 'Perdas cambiais', 'expense', 'fx_loss', 'debit', '5000'),
            self::account('7200', 'Corporation tax expense', 'Podatek dochodowy', 'Imposto sobre sociedades', 'expense', 'corporation_tax', 'debit', '5000'),
            self::account('7900', 'Other expenses', 'Pozostałe koszty', 'Outros gastos', 'expense', 'other', 'debit', '5000'),
        ];
    }

    /** @return array{code: string, name: array{en: string, pl: string, pt: string}, type: string, subtype: string, normal_balance: string, parent_code: string|null, is_postable: bool} */
    private static function account(string $code, string $en, string $pl, string $pt, string $type, string $subtype, string $normalBalance, ?string $parentCode, bool $isPostable = true): array
    {
        return [
            'code' => $code,
            'name' => compact('en', 'pl', 'pt'),
            'type' => $type,
            'subtype' => $subtype,
            'normal_balance' => $normalBalance,
            'parent_code' => $parentCode,
            'is_postable' => $isPostable,
        ];
    }
}
