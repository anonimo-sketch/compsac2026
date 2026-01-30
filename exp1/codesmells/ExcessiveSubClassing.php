<?php

/**
 * Code Smell: Class Complexity
 * Descrição: Classe com complexidade excessiva, indicando possível responsabilidade excessiva
 * Esta classe demonstra o code smell "Class Complexity" com:
 * - Muitas responsabilidades diferentes (Viola o princípio de Responsabilidade Única)
 * - Métodos longos e complexos
 * - Muitos parâmetros
 * - Alto acoplamento
 * - Muitas variáveis de instância
 * - Lógica condicional complexa
 */
class ExcessiveSubClassing
{
}

class Report {
    public function generate() {
        return "Relatório padrão";
    }
}

class PDFReport extends Report {
    public function generate() {
        return "Gerando relatório em PDF";
    }
}

class ExcelReport extends Report {
    public function generate() {
        return "Gerando relatório em Excel";
    }
}

class HTMLReport extends Report {
    public function generate() {
        return "Gerando relatório em HTML";
    }
}
