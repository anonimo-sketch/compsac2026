<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithHighMethodComplexity extends Controller
{
    public function generateMonthlyReport(array $salesData, array $inventoryData, array $employeeData): string {
        $report = "Monthly Report\n";
        $report .= "====================\n";

        // Sales summary
        $totalSales = 0;
        foreach ($salesData as $sale) {
            $report .= "Sale: {$sale['product']} - {$sale['amount']}\n";
            $totalSales += $sale['amount'];
        }

        $report .= "Total Sales: $totalSales\n\n";

        // Inventory status
        $report .= "Inventory:\n";
        foreach ($inventoryData as $item) {
            if ($item['quantity'] < 10) {
                $report .= "Low stock: {$item['name']} ({$item['quantity']} units)\n";
            } else {
                $report .= "In stock: {$item['name']} ({$item['quantity']} units)\n";
            }
        }

        $report .= "\n";

        // Employee status
        $report .= "Employees:\n";
        foreach ($employeeData as $employee) {
            if ($employee['status'] === 'active') {
                $report .= "Active: {$employee['name']}\n";
            } elseif ($employee['status'] === 'vacation') {
                $report .= "On vacation: {$employee['name']}\n";
            } else {
                $report .= "Inactive: {$employee['name']}\n";
            }
        }

        return $report;
    }
}
