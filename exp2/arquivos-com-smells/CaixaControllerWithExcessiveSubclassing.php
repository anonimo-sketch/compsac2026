<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithExcessiveSubclassing extends Controller
{
}

class Report
{
    public function generate()
    {
        return "Relatório padrão";
    }
}

class PDFReport extends Report
{
    public function generate()
    {
        return "Gerando relatório em PDF";
    }
}

class ExcelReport extends Report
{
    public function generate()
    {
        return "Gerando relatório em Excel";
    }
}

class HTMLReport extends Report
{
    public function generate()
    {
        return "Gerando relatório em HTML";
    }
}
