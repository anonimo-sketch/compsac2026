<?php
// Superclasse
abstract class Report {
    abstract public function generate();
}

// Subclasses
class SalesReport extends Report {
    public function generate() {
        echo "Generating Sales Report";
    }
}

class InventoryReport extends Report {
    public function generate() {
        echo "Generating Inventory Report";
    }
}

class EmployeeReport extends Report {
    public function generate() {
        echo "Generating Employee Report";
    }
}

class CustomerReport extends Report {
    public function generate() {
        echo "Generating Customer Report";
    }
}

class FinanceReport extends Report {
    public function generate() {
        echo "Generating Finance Report";
    }
}

class LogisticsReport extends Report {
    public function generate() {
        echo "Generating Logistics Report";
    }
}
