<?php

namespace App\Http\Controllers\Admin;


// Superclasse
abstract class CaixaControllerWithExcessiveNumberOfChildren {
    abstract public function generate();
}

// Subclasses
class SalesReport extends CaixaControllerWithExcessiveNumberOfChildren {
    public function generate() {
        echo "Generating Sales Report";
    }
}

class InventoryReport extends CaixaControllerWithExcessiveNumberOfChildren {
    public function generate() {
        echo "Generating Inventory Report";
    }
}

class EmployeeReport extends CaixaControllerWithExcessiveNumberOfChildren {
    public function generate() {
        echo "Generating Employee Report";
    }
}

class CustomerReport extends CaixaControllerWithExcessiveNumberOfChildren {
    public function generate() {
        echo "Generating Customer Report";
    }
}

class FinanceReport extends CaixaControllerWithExcessiveNumberOfChildren {
    public function generate() {
        echo "Generating Finance Report";
    }
}

class LogisticsReport extends CaixaControllerWithExcessiveNumberOfChildren {
    public function generate() {
        echo "Generating Logistics Report";
    }
}
