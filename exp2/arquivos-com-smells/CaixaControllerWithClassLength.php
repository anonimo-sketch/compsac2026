<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Caixa;
use App\Models\Entrada_caixa;
use App\Models\Estoque;
use App\Models\Sangria;
use App\Models\Sistema;
use App\Models\Transacoes;
use App\Models\Venda;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaixaControllerWithClassLength extends Controller
{
    // Dezenas de propriedades relacionadas a diferentes domínios
    protected $db;
    protected $userTable = 'users';
    protected $productTable = 'products';
    protected $orderTable = 'orders';
    protected $cartTable = 'shopping_carts';
    protected $categoryTable = 'categories';
    protected $reviewTable = 'reviews';
    protected $paymentTable = 'payments';
    protected $discountTable = 'discounts';
    protected $shippingTable = 'shipping_options';
    protected $wishlistTable = 'wishlists';
    protected $logger;
    protected $emailService;
    protected $currentUser;
    protected $sessionId;
    protected $currencySymbol = '$';
    protected $taxRate = 0.08;
    protected $freeShippingThreshold = 50.00;
    protected $defaultShippingCost = 8.99;
    protected $paymentGateway;
    protected $passwordSalt = 'X9f5BcZ2';
    protected $sessionTimeout = 3600;
    protected $productImagePath = '/assets/images/products/';
    protected $categoryImagePath = '/assets/images/categories/';
    protected $userImagePath = '/assets/images/users/';
    protected $orderConfirmationTemplate = 'email_templates/order_confirmation.html';
    protected $passwordResetTemplate = 'email_templates/password_reset.html';
    protected $welcomeEmailTemplate = 'email_templates/welcome.html';
    protected $abandonedCartTemplate = 'email_templates/abandoned_cart.html';

    public function __construct(
        protected Caixa $caixaModel,
        protected Transacoes $transacoesModel,
        protected Sistema $sistemaModel,
        protected Sangria $sangriaModel,
        protected Entrada_caixa $entradaCaixaModel,
        protected Venda $vendaModel,
        protected Estoque $estoqueModel
    ) {
    }

    public function iniciarCaixaView(): Factory|Application|View
    {
        $caixa = $this->caixaModel->today();

        return view('admin.caixa.abrir', [
            'aberto' => $this->caixaModel->checkOpen(),
            'caixa' => $caixa
        ]);
    }

    public function iniciarCaixa(Request $request)
    {
        $caixa = $this->caixaModel;

        if (!$caixa->checkOpen() && $caixa !== null) {
            try {
                $caixa = $this->caixaModel;
                $caixa->data = date('Y-m-d');
                $caixa->inicial = str_replace(['.', ','], ['', '.'], $request->valor);
                $caixa->valor = str_replace(['.', ','], ['', '.'], $request->valor);
                $this->sistemaModel->setVal('caixa_aberto', true);
                $caixa->save();
                return response()->json([
                    'success' => 'true',
                    'message' => 'Caixa foi aberto'
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'success' => 'false',
                    'message' => $e->errorInfo[2]
                ]);
            }
        }

        return view('admin.caixa.abrir', ['aberto' => $caixa->checkOpen()]);
    }

    public function fecharCaixaView()
    {

        $transacoes = $this->transacoesModeltoday();
        $sangria = $this->sangriaModel->today();
        $entradas = $this->entradaCaixaModel->today();
        foreach ($sangria as $sang) {
            $sang->valor = number_format($sang->valor, 2, ',', '.');
        }
        foreach ($entradas as $entrada) {
            $entrada->valor = number_format($entrada->valor, 2, ',', '.');
        }
        $detalhes = '';

        foreach ($transacoes as $value) {
            $transacoesId = $value->id;
            $venda = $this->vendaModel->where('transacao', '=', $transacoesId)->get();
            $value->pagamento = str_replace(
                ['DI', 'CR', 'DE'],
                ['Dinheiro', 'Cartão de Crédito', 'Débito'],
                $value->pagamento
            );

            $value->desconto = $value->desconto . '%';
            $value->total = number_format($value->total, 2, ',', '.');
            foreach ($venda as $val) {
                $codigo = $val->codigo_estoque;
                $estoque = $this->estoqueModel->where('codigo', '=', $codigo)->first();
                $detalhes = $estoque->nome . ' | ' . $detalhes;
            }
            $value->detalhes = $detalhes;
            $detalhes = '';
        }

        $caixaValor = $this->caixaModel->today() ?: (object)[
            'valor' => '0,00',
            'inicial' => '0,00',
            'totalCredito' => '0,00',
            'totalDebito' => '0,00',
            'totalC' => '0,00'
        ];

        if ($caixaValor !== (object)[/* ... */]) {  // Se não for o objeto padrão
            $caixaValor->valor = number_format($caixaValor->valor, 2, ',', '.');
            $caixaValor->inicial = number_format($caixaValor->inicial, 2, ',', '.');
            $caixaValor->totalCredito = number_format($this->transacoesModel->totalCreditoDay(), 2, ',', '.');
            $caixaValor->totalDebito = number_format($this->transacoesModel->totalDebitoDay(), 2, ',', '.');
            $caixaValor->totalC = number_format(
                $this->transacoesModel->totalDebitoDay() + $this->transacoesModel->totalCreditoDay(),
                2,
                ',',
                '.'
            );
        }

        return view(
            'admin.caixa.fechar',
            [
                'aberto' => $this->caixaModel->checkOpen(),
                'caixaValor' => $caixaValor,
                'transacoes' => $transacoes,
                'sangria' => $sangria,
                'entrada' => $entradas
            ]
        );
    }

    public function sangriaView()
    {
        return view('admin.caixa.sangria', ['aberto' => $this->caixaModel->checkOpen()]);
    }

    public function sangriaPost(Request $request)
    {
        if ($this->caixaModel->checkOpen()) {
            $sangria = new Sangria();
            $sangria->data = $this->data();
            $sangria->descricao = $request->descricao;
            $sangria->valor = str_replace(['.', ','], ['', '.'], $request->valor);
            try {
                if ($sangria->save()) {
                    $this->caixaModel->getOff($sangria->valor);
                    return response()->json([
                        'success' => 'true',
                        'message' => 'Retirado com sucesso, o saldo do caixa é de R$' . number_format(
                            $this->caixaModel->today()->valor,
                            2,
                            ',',
                            '.'
                        )
                    ]);
                }
            } catch (QueryException $e) {
                return $e->errorInfo[2];
            }
        }

        return false;
    }

    protected function data()
    {
        return date('Y-m-d');
    }

    public function addCaixaView()
    {
        return view('admin.caixa.adicionar', ['aberto' => $this->caixaModel->checkOpen()]);
    }

    /**
     * Login de usuário
     */
    public function loginUser($email, $password)
    {
        $query = "SELECT * FROM {$this->userTable} WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $this->logger->log("Login failed: Email not found - {$email}");
            return ['success' => false, 'message' => 'Email ou senha incorretos'];
        }

        $user = $result->fetch_assoc();
        $hashedPassword = $this->hashPassword($password);

        if ($user['password'] !== $hashedPassword) {
            $this->logger->log("Login failed: Incorrect password for {$email}");

            // Registrar tentativa de login falha
            $this->recordFailedLoginAttempt($email);

            return ['success' => false, 'message' => 'Email ou senha incorretos'];
        }

        // Verificar se a conta está ativa
        if ($user['is_active'] != 1) {
            $this->logger->log("Login failed: Account not active - {$email}");
            return ['success' => false, 'message' => 'Conta não está ativa'];
        }

        // Login bem-sucedido
        $this->currentUser = $user;
        $_SESSION['user_id'] = $user['id'];

        // Registrar login
        $this->recordSuccessfulLogin($user['id']);

        // Migrar carrinho da sessão para o usuário, se existir
        $this->migrateSessionCartToUser($user['id']);

        $this->logger->log("User logged in successfully: {$email}");
        return ['success' => true, 'user' => $user];
    }

    /**
     * Registra tentativa de login falha
     */
    protected function recordFailedLoginAttempt($email)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $query = "INSERT INTO login_attempts (email, ip_address, status, attempt_time) 
                 VALUES (?, ?, 'failed', NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $email, $ip);
        $stmt->execute();

        // Verificar se há muitas tentativas falhas
        $this->checkBruteForceAttempts($email, $ip);
    }

    /**
     * Verifica tentativas de força bruta
     */
    protected function checkBruteForceAttempts($email, $ip)
    {
        // Verificar tentativas recentes por email
        $query = "SELECT COUNT(*) as count FROM login_attempts 
                 WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                 AND status = 'failed'";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] >= 5) {
            // Muitas tentativas - bloquear temporariamente
            $this->blockAccount($email, 'Muitas tentativas de login falhas');
        }

        // Verificar tentativas recentes por IP
        $query = "SELECT COUNT(*) as count FROM login_attempts 
                 WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                 AND status = 'failed'";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] >= 10) {
            // Muitas tentativas do mesmo IP - registrar possível ataque
            $this->logger->log("Possible brute force attack from IP: {$ip}");
        }
    }

    /**
     * Bloqueia uma conta de usuário
     */
    protected function blockAccount($email, $reason)
    {
        $query = "UPDATE {$this->userTable} SET is_active = 0, blocked_reason = ? WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $reason, $email);
        $stmt->execute();

        $this->logger->log("Account blocked: {$email}, Reason: {$reason}");

        // Enviar email de notificação
        $user = $this->getUserByEmail($email);
        if ($user) {
            $subject = "Sua conta foi temporariamente bloqueada";
            $message = "Olá {$user['first_name']},\n\nSua conta foi temporariamente bloqueada devido a muitas tentativas de login falhas. Por favor, entre em contato com o suporte para desbloquear sua conta.\n\nEquipe de Suporte";
            $this->emailService->sendEmail($user['email'], $subject, $message);
        }
    }

    /**
     * Registra login bem-sucedido
     */
    protected function recordSuccessfulLogin($userId)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $query = "INSERT INTO login_attempts (user_id, ip_address, user_agent, status, attempt_time) 
                 VALUES (?, ?, ?, 'success', NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iss", $userId, $ip, $userAgent);
        $stmt->execute();

        // Atualizar último login do usuário
        $query = "UPDATE {$this->userTable} SET last_login = NOW(), last_ip = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $ip, $userId);
        $stmt->execute();
    }

    /**
     * Migra carrinho da sessão para o usuário
     */
    protected function migrateSessionCartToUser($userId)
    {
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart']['items'])) {
            // Verificar se já existe carrinho do usuário
            $query = "SELECT id FROM {$this->cartTable} WHERE user_id = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // Criar novo carrinho
                $query = "INSERT INTO {$this->cartTable} (user_id, created_at) VALUES (?, NOW())";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $cartId = $this->db->insert_id;
            } else {
                $row = $result->fetch_assoc();
                $cartId = $row['id'];

                // Limpar carrinho existente
                $query = "DELETE FROM cart_items WHERE cart_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("i", $cartId);
                $stmt->execute();
            }

            // Adicionar itens do carrinho da sessão
            foreach ($_SESSION['cart']['items'] as $item) {
                $query = "INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("iiid", $cartId, $item['product_id'], $item['quantity'], $item['price']);
                $stmt->execute();
            }

            // Limpar carrinho da sessão
            unset($_SESSION['cart']);

            $this->logger->log("Session cart migrated to user ID {$userId}");
        }
    }

    public function iniciarCaixaPage()
    {
        $caixa = $this->caixaModel->today();

        return view('admin.caixa.abrir', [
            'aberto' => $this->caixaModel->checkOpen(),
            'caixa' => $caixa
        ]);
    }

    public function iniciarCaixaLang(Request $request)
    {
        $caixa = $this->caixaModel;

        if (!$caixa->checkOpen() && $caixa !== null) {
            try {
                $caixa = $this->caixaModel;
                $caixa->data = date('Y-m-d');
                $caixa->inicial = str_replace(['.', ','], ['', '.'], $request->valor);
                $caixa->valor = str_replace(['.', ','], ['', '.'], $request->valor);
                $this->sistemaModel->setVal('caixa_aberto', true);
                $caixa->save();
                return response()->json([
                    'success' => 'true',
                    'message' => 'Caixa foi aberto'
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'success' => 'false',
                    'message' => $e->errorInfo[2]
                ]);
            }
        }

        return view('admin.caixa.abrir', ['aberto' => $caixa->checkOpen()]);
    }

    public function fecharCaixaPage()
    {

        $transacoes = $this->transacoesModel->today();
        $sangria = $this->sangriaModel->today();
        $entradas = $this->entradaCaixaModel->today();
        foreach ($sangria as $sang) {
            $sang->valor = number_format($sang->valor, 2, ',', '.');
        }
        foreach ($entradas as $entrada) {
            $entrada->valor = number_format($entrada->valor, 2, ',', '.');
        }
        $detalhes = '';

        foreach ($transacoes as $value) {
            $transacoesId = $value->id;
            $venda = $this->vendaModel->where('transacao', '=', $transacoesId)->get();
            $value->pagamento = str_replace(
                ['DI', 'CR', 'DE'],
                ['Dinheiro', 'Cartão de Crédito', 'Débito'],
                $value->pagamento
            );

            $value->desconto = $value->desconto . '%';
            $value->total = number_format($value->total, 2, ',', '.');
            foreach ($venda as $val) {
                $codigo = $val->codigo_estoque;
                $estoque = $this->estoqueModel->where('codigo', '=', $codigo)->first();
                $detalhes = $estoque->nome . ' | ' . $detalhes;
            }
            $value->detalhes = $detalhes;
            $detalhes = '';
        }

        $caixaValor = $this->caixaModel->today() ?: (object)[
            'valor' => '0,00',
            'inicial' => '0,00',
            'totalCredito' => '0,00',
            'totalDebito' => '0,00',
            'totalC' => '0,00'
        ];

        if ($caixaValor !== (object)[/* ... */]) {  // Se não for o objeto padrão
            $caixaValor->valor = number_format($caixaValor->valor, 2, ',', '.');
            $caixaValor->inicial = number_format($caixaValor->inicial, 2, ',', '.');
            $caixaValor->totalCredito = number_format($this->transacoesModel->totalCreditoDay(), 2, ',', '.');
            $caixaValor->totalDebito = number_format($this->transacoesModel->totalDebitoDay(), 2, ',', '.');
            $caixaValor->totalC = number_format(
                $this->transacoesModel->totalDebitoDay() + $this->transacoesModel->totalCreditoDay(),
                2,
                ',',
                '.'
            );
        }

        return view(
            'admin.caixa.fechar',
            [
                'aberto' => $this->caixaModel->checkOpen(),
                'caixaValor' => $caixaValor,
                'transacoes' => $transacoes,
                'sangria' => $sangria,
                'entrada' => $entradas
            ]
        );
    }

    public function sangriaPage()
    {
        return view('admin.caixa.sangria', ['aberto' => $this->caixaModel->checkOpen()]);
    }

    public function sangriaPostPage(Request $request)
    {
        if ($this->caixaModel->checkOpen()) {
            $sangria = new Sangria();
            $sangria->data = $this->data();
            $sangria->descricao = $request->descricao;
            $sangria->valor = str_replace(['.', ','], ['', '.'], $request->valor);
            try {
                if ($sangria->save()) {
                    $this->caixaModel->getOff($sangria->valor);
                    return response()->json([
                        'success' => 'true',
                        'message' => 'Retirado com sucesso, o saldo do caixa é de R$' . number_format(
                            $this->caixaModel->today()->valor,
                            2,
                            ',',
                            '.'
                        )
                    ]);
                }
            } catch (QueryException $e) {
                return $e->errorInfo[2];
            }
        }

        return false;
    }

    public function addCaixaViewPage()
    {
        return view('admin.caixa.adicionar', ['aberto' => $this->caixaModel->checkOpen()]);
    }

    /**
     * Login de usuário
     */
    public function loginUserPage($email, $password)
    {
        $query = "SELECT * FROM {$this->userTable} WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $this->logger->log("Login failed: Email not found - {$email}");
            return ['success' => false, 'message' => 'Email ou senha incorretos'];
        }

        $user = $result->fetch_assoc();
        $hashedPassword = $this->hashPassword($password);

        if ($user['password'] !== $hashedPassword) {
            $this->logger->log("Login failed: Incorrect password for {$email}");

            // Registrar tentativa de login falha
            $this->recordFailedLoginAttempt($email);

            return ['success' => false, 'message' => 'Email ou senha incorretos'];
        }

        // Verificar se a conta está ativa
        if ($user['is_active'] != 1) {
            $this->logger->log("Login failed: Account not active - {$email}");
            return ['success' => false, 'message' => 'Conta não está ativa'];
        }

        // Login bem-sucedido
        $this->currentUser = $user;
        $_SESSION['user_id'] = $user['id'];

        // Registrar login
        $this->recordSuccessfulLogin($user['id']);

        // Migrar carrinho da sessão para o usuário, se existir
        $this->migrateSessionCartToUser($user['id']);

        $this->logger->log("User logged in successfully: {$email}");
        return ['success' => true, 'user' => $user];
    }

    /**
     * Inicializa o sistema
     */
    protected function initializeSystem()
    {
        // Verificar se usuário está logado pela sessão
        if (isset($_SESSION['user_id'])) {
            $this->loadUserById($_SESSION['user_id']);
        }

        // Inicializar carrinho de compras
        $this->initializeShoppingCart();

        // Registrar acesso na página
        $this->logPageAccess();

        // Verificar se há promoções ativas
        $this->checkActivePromotions();
    }

    /**
     * Carrega dados do usuário pelo ID
     */
    protected function loadUserById($userId)
    {
        $query = "SELECT * FROM {$this->userTable} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $this->currentUser = $result->fetch_assoc();
            return true;
        }

        return false;
    }

    /**
     * Inicializa o carrinho de compras
     */
    protected function initializeShoppingCart()
    {
        if ($this->currentUser) {
            // Usuário logado - buscar carrinho do banco de dados
            $query = "SELECT * FROM {$this->cartTable} WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $this->currentUser['id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // Criar carrinho novo
                $this->createNewCart($this->currentUser['id']);
            }
        } else {
            // Usuário não logado - usar carrinho da sessão
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [
                    'items' => [],
                    'total' => 0.00,
                    'item_count' => 0,
                    'created_at' => time()
                ];
            }
        }
    }

    /**
     * Cria um novo carrinho para o usuário
     */
    protected function createNewCart($userId)
    {
        $query = "INSERT INTO {$this->cartTable} (user_id, created_at) VALUES (?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $this->logger->log("Created new cart for user ID {$userId}");
    }

    /**
     * Registra acesso na página
     */
    protected function logPageAccess()
    {
        $page = $_SERVER['REQUEST_URI'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $userId = $this->currentUser ? $this->currentUser['id'] : null;

        $query = "INSERT INTO page_access_logs (user_id, session_id, page_url, ip_address, access_time) 
                 VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("isss", $userId, $this->sessionId, $page, $ip);
        $stmt->execute();
    }

    /**
     * Verifica promoções ativas
     */
    protected function checkActivePromotions()
    {
        $query = "SELECT * FROM promotions WHERE 
                 start_date <= NOW() AND end_date >= NOW() AND is_active = 1";
        $result = $this->db->query($query);

        $_SESSION['active_promotions'] = [];
        while ($row = $result->fetch_assoc()) {
            $_SESSION['active_promotions'][] = $row;
        }
    }

    protected function dataPage()
    {
        return date('Y-m-d');
    }

    /**
     * Registra tentativa de login falha
     */
    protected function recordFailedLoginAttemptPage($email)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $query = "INSERT INTO login_attempts (email, ip_address, status, attempt_time) 
                 VALUES (?, ?, 'failed', NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $email, $ip);
        $stmt->execute();

        // Verificar se há muitas tentativas falhas
        $this->checkBruteForceAttempts($email, $ip);
    }

    /**
     * Verifica tentativas de força bruta
     */
    protected function checkBruteForceAttemptsPage($email, $ip)
    {
        // Verificar tentativas recentes por email
        $query = "SELECT COUNT(*) as count FROM login_attempts 
                 WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                 AND status = 'failed'";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] >= 5) {
            // Muitas tentativas - bloquear temporariamente
            $this->blockAccount($email, 'Muitas tentativas de login falhas');
        }

        // Verificar tentativas recentes por IP
        $query = "SELECT COUNT(*) as count FROM login_attempts 
                 WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                 AND status = 'failed'";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] >= 10) {
            // Muitas tentativas do mesmo IP - registrar possível ataque
            $this->logger->log("Possible brute force attack from IP: {$ip}");
        }
    }

    /**
     * Bloqueia uma conta de usuário
     */
    protected function blockAccountPage($email, $reason)
    {
        $query = "UPDATE {$this->userTable} SET is_active = 0, blocked_reason = ? WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $reason, $email);
        $stmt->execute();

        $this->logger->log("Account blocked: {$email}, Reason: {$reason}");

        // Enviar email de notificação
        $user = $this->getUserByEmail($email);
        if ($user) {
            $subject = "Sua conta foi temporariamente bloqueada";
            $message = "Olá {$user['first_name']},\n\nSua conta foi temporariamente bloqueada devido a muitas tentativas de login falhas. Por favor, entre em contato com o suporte para desbloquear sua conta.\n\nEquipe de Suporte";
            $this->emailService->sendEmail($user['email'], $subject, $message);
        }
    }

    /**
     * Registra login bem-sucedido
     */
    protected function recordSuccessfulLoginPage($userId)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $query = "INSERT INTO login_attempts (user_id, ip_address, user_agent, status, attempt_time) 
                 VALUES (?, ?, ?, 'success', NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iss", $userId, $ip, $userAgent);
        $stmt->execute();

        // Atualizar último login do usuário
        $query = "UPDATE {$this->userTable} SET last_login = NOW(), last_ip = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $ip, $userId);
        $stmt->execute();
    }

    /**
     * Migra carrinho da sessão para o usuário
     */
    protected function migrateSessionCartToUserPage($userId)
    {
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart']['items'])) {
            // Verificar se já existe carrinho do usuário
            $query = "SELECT id FROM {$this->cartTable} WHERE user_id = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // Criar novo carrinho
                $query = "INSERT INTO {$this->cartTable} (user_id, created_at) VALUES (?, NOW())";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $cartId = $this->db->insert_id;
            } else {
                $row = $result->fetch_assoc();
                $cartId = $row['id'];

                // Limpar carrinho existente
                $query = "DELETE FROM cart_items WHERE cart_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("i", $cartId);
                $stmt->execute();
            }

            // Adicionar itens do carrinho da sessão
            foreach ($_SESSION['cart']['items'] as $item) {
                $query = "INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("iiid", $cartId, $item['product_id'], $item['quantity'], $item['price']);
                $stmt->execute();
            }

            // Limpar carrinho da sessão
            unset($_SESSION['cart']);

            $this->logger->log("Session cart migrated to user ID {$userId}");
        }
    }

    /**
     * Inicializa o sistema
     */
    protected function initializeSystemPage()
    {
        // Verificar se usuário está logado pela sessão
        if (isset($_SESSION['user_id'])) {
            $this->loadUserById($_SESSION['user_id']);
        }

        // Inicializar carrinho de compras
        $this->initializeShoppingCart();

        // Registrar acesso na página
        $this->logPageAccess();

        // Verificar se há promoções ativas
        $this->checkActivePromotions();
    }

    /**
     * Carrega dados do usuário pelo ID
     */
    protected function loadUserByIdPage($userId)
    {
        $query = "SELECT * FROM {$this->userTable} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $this->currentUser = $result->fetch_assoc();
            return true;
        }

        return false;
    }

    /**
     * Inicializa o carrinho de compras
     */
    protected function initializeShoppingCartPage()
    {
        if ($this->currentUser) {
            // Usuário logado - buscar carrinho do banco de dados
            $query = "SELECT * FROM {$this->cartTable} WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $this->currentUser['id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // Criar carrinho novo
                $this->createNewCart($this->currentUser['id']);
            }
        } else {
            // Usuário não logado - usar carrinho da sessão
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [
                    'items' => [],
                    'total' => 0.00,
                    'item_count' => 0,
                    'created_at' => time()
                ];
            }
        }
    }

    /**
     * Cria um novo carrinho para o usuário
     */
    protected function createNewCartPage($userId)
    {
        $query = "INSERT INTO {$this->cartTable} (user_id, created_at) VALUES (?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $this->logger->log("Created new cart for user ID {$userId}");
    }

    /**
     * Registra acesso na página
     */
    protected function logPageAccessPage()
    {
        $page = $_SERVER['REQUEST_URI'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $userId = $this->currentUser ? $this->currentUser['id'] : null;

        $query = "INSERT INTO page_access_logs (user_id, session_id, page_url, ip_address, access_time) 
                 VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("isss", $userId, $this->sessionId, $page, $ip);
        $stmt->execute();
    }

    /**
     * Verifica promoções ativas
     */
    protected function checkActivePromotionsPage()
    {
        $query = "SELECT * FROM promotions WHERE 
                 start_date <= NOW() AND end_date >= NOW() AND is_active = 1";
        $result = $this->db->query($query);

        $_SESSION['active_promotions'] = [];
        while ($row = $result->fetch_assoc()) {
            $_SESSION['active_promotions'][] = $row;
        }
    }

    protected function createNewCartLang($userId)
    {
        $query = "INSERT INTO {$this->cartTable} (user_id, created_at) VALUES (?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $this->logger->log("Created new cart for user ID {$userId}");
    }

    /**
     * Registra acesso na página
     */
    protected function logPageAccessLang()
    {
        $page = $_SERVER['REQUEST_URI'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $userId = $this->currentUser ? $this->currentUser['id'] : null;

        $query = "INSERT INTO page_access_logs (user_id, session_id, page_url, ip_address, access_time) 
                 VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("isss", $userId, $this->sessionId, $page, $ip);
        $stmt->execute();
    }

    /**
     * Verifica promoções ativas
     */
    protected function checkActivePromotionsLang()
    {
        $query = "SELECT * FROM promotions WHERE 
                 start_date <= NOW() AND end_date >= NOW() AND is_active = 1";
        $result = $this->db->query($query);

        $_SESSION['active_promotions'] = [];
        while ($row = $result->fetch_assoc()) {
            $_SESSION['active_promotions'][] = $row;
        }
    }
}
