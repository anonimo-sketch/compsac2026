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
class UserManager
{
    protected $db;
    protected $logger;
    protected $emailSender;
    protected $smsGateway;
    protected $userCache;
    protected $config;
    protected $paymentProcessor;
    protected $fileStorage;
    protected $sessionManager;
    protected $encryptor;

    public function __construct($db, $logger, $emailSender, $smsGateway, $userCache, $config, $paymentProcessor, $fileStorage, $sessionManager, $encryptor)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->emailSender = $emailSender;
        $this->smsGateway = $smsGateway;
        $this->userCache = $userCache;
        $this->config = $config;
        $this->paymentProcessor = $paymentProcessor;
        $this->fileStorage = $fileStorage;
        $this->sessionManager = $sessionManager;
        $this->encryptor = $encryptor;
    }

    /**
     * Registra um novo usuário com muitos parâmetros e lógica complexa
     */
    public function registerUser($username, $password, $email, $firstName, $lastName, $address, $city, $state, $country, $zipCode, $phone, $birthdate, $gender, $referralCode, $marketingConsent, $paymentMethod, $subscription, $profilePicture = null)
    {
        // Validação de entrada com muitas condições aninhadas
        if (empty($username)) {
            throw new Exception("Username is required");
        } elseif (strlen($username) < 3) {
            throw new Exception("Username must be at least 3 characters");
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new Exception("Username can only contain letters, numbers and underscores");
        }

        // Verificação de senha com várias condições
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        } elseif (!preg_match('/[A-Z]/', $password)) {
            throw new Exception("Password must contain at least one uppercase letter");
        } elseif (!preg_match('/[a-z]/', $password)) {
            throw new Exception("Password must contain at least one lowercase letter");
        } elseif (!preg_match('/[0-9]/', $password)) {
            throw new Exception("Password must contain at least one number");
        } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            throw new Exception("Password must contain at least one special character");
        }

        // Verificar se o usuário já existe
        $user = $this->db->query("SELECT * FROM users WHERE username = '$username' OR email = '$email'");
        if ($user->num_rows > 0) {
            throw new Exception("Username or email already exists");
        }

        // Processar o upload de imagem do perfil
        $profilePicturePath = null;
        if ($profilePicture !== null) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($profilePicture['type'], $allowedTypes)) {
                throw new Exception("Only JPEG, PNG and GIF images are allowed");
            }

            if ($profilePicture['size'] > 5 * 1024 * 1024) { // 5MB
                throw new Exception("Profile picture size must be less than 5MB");
            }

            $extension = pathinfo($profilePicture['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $profilePicturePath = 'uploads/profiles/' . $filename;

            if (!move_uploaded_file($profilePicture['tmp_name'], $profilePicturePath)) {
                throw new Exception("Failed to upload profile picture");
            }

            // Redimensionar a imagem
            $this->resizeImage($profilePicturePath);
        }

        // Processar o pagamento
        $paymentResult = null;
        if ($subscription !== null) {
            $paymentResult = $this->paymentProcessor->processPayment($paymentMethod, $subscription['price']);
            if (!$paymentResult['success']) {
                throw new Exception("Payment failed: " . $paymentResult['message']);
            }
        }

        // Criptografar a senha
        $hashedPassword = $this->encryptor->hash($password);

        // Inserir o usuário no banco de dados
        $query = "INSERT INTO users (username, password, email, first_name, last_name, address, city, state, country, zip_code, phone, birthdate, gender, referral_code, marketing_consent, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssssssssssssssis", $username, $hashedPassword, $email, $firstName, $lastName, $address, $city, $state, $country, $zipCode, $phone, $birthdate, $gender, $referralCode, $marketingConsent, $profilePicturePath);
        $stmt->execute();
        $userId = $stmt->insert_id;

        // Criar a assinatura se necessário
        if ($subscription !== null && $paymentResult['success']) {
            $this->createSubscription($userId, $subscription['plan'], $subscription['price'], $paymentResult['transaction_id']);
        }

        // Registrar o evento de registro
        $this->logger->log("User registered: $username ($userId)");

        // Adicionar o usuário ao cache
        $userData = [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'full_name' => "$firstName $lastName"
        ];
        $this->userCache->set("user_$userId", $userData, 3600);

        // Enviar e-mail de boas-vindas
        $this->sendWelcomeEmail($email, $firstName);

        // Enviar SMS de confirmação
        if (!empty($phone)) {
            $this->sendWelcomeSMS($phone, $firstName);
        }

        // Criar sessão de usuário
        $this->sessionManager->createSession($userId);

        return $userId;
    }

    /**
     * Redimensiona uma imagem com lógica complexa embutida
     */
    protected function resizeImage($imagePath)
    {
        $image = null;
        $type = exif_imagetype($imagePath);

        // Carregar a imagem com base no tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($imagePath);
                break;
            default:
                throw new Exception("Unsupported image type");
        }

        // Obter dimensões da imagem
        $width = imagesx($image);
        $height = imagesy($image);

        // Calcular nova dimensão
        $maxDimension = 1000;
        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = intval($height * $maxDimension / $width);
            } else {
                $newHeight = $maxDimension;
                $newWidth = intval($width * $maxDimension / $height);
            }

            // Criar a nova imagem
            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preservar a transparência para PNG
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Redimensionar a imagem
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Salvar a imagem redimensionada
            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($newImage, $imagePath, 85);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($newImage, $imagePath, 9);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($newImage, $imagePath);
                    break;
            }

            // Liberar a memória
            imagedestroy($image);
            imagedestroy($newImage);
        }
    }

    /**
     * Método para enviar um email de boas-vindas
     */
    protected function sendWelcomeEmail($email, $firstName)
    {
        $subject = "Bem-vindo ao nosso serviço!";
        $body = "Olá $firstName,\n\nBem-vindo ao nosso serviço. Estamos felizes em tê-lo como usuário.\n\nAtenciosamente,\nEquipe de suporte";

        $this->emailSender->send($email, $subject, $body);
    }

    /**
     * Método para enviar um SMS de boas-vindas
     */
    protected function sendWelcomeSMS($phone, $firstName)
    {
        $message = "Olá $firstName, bem-vindo ao nosso serviço!";
        $this->smsGateway->send($phone, $message);
    }

    /**
     * Método que faz várias coisas diferentes
     */
    public function processUserData($userId, $data, $options = [])
    {
        // Verificar se o usuário existe
        $user = $this->getUserById($userId);
        if (!$user) {
            throw new Exception("User not found");
        }

        // Processar atualizações de perfil
        if (isset($data['profile'])) {
            $this->updateUserProfile($userId, $data['profile']);
        }

        // Processar mudanças de assinatura
        if (isset($data['subscription'])) {
            $this->updateUserSubscription($userId, $data['subscription']);
        }

        // Processar preferências de notificação
        if (isset($data['notifications'])) {
            $this->updateNotificationPreferences($userId, $data['notifications']);
        }

        // Processar atualizações de pagamento
        if (isset($data['payment'])) {
            $this->updatePaymentMethods($userId, $data['payment']);
        }

        // Processar atividades de login
        if (isset($data['login'])) {
            $this->processLoginActivity($userId, $data['login']);
        }

        // Limpar cache do usuário
        $this->userCache->delete("user_$userId");

        // Registrar as mudanças
        $this->logger->log("User data processed: $userId");

        return true;
    }

    /**
     * Método para verificar se um usuário pode acessar um recurso
     * Lógica complexa com muitas condições
     */
    public function canAccessResource($userId, $resourceId, $action)
    {
        // Verificar se o usuário existe
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }

        // Verificar se o usuário é um administrador (pode acessar tudo)
        if ($user['role'] === 'admin') {
            $this->logger->log("Admin access granted: $userId to resource $resourceId");
            return true;
        }

        // Verificar se o recurso existe
        $resource = $this->getResourceById($resourceId);
        if (!$resource) {
            return false;
        }

        // Verificar se o recurso é público
        if ($resource['is_public']) {
            return true;
        }

        // Verificar se o usuário é o proprietário do recurso
        if ($resource['owner_id'] === $userId) {
            return true;
        }

        // Verificar permissões específicas
        $permissions = $this->getPermissions($userId, $resourceId);
        if (isset($permissions[$action]) && $permissions[$action]) {
            return true;
        }

        // Verificar permissões de grupo
        $userGroups = $this->getUserGroups($userId);
        foreach ($userGroups as $groupId) {
            $groupPermissions = $this->getGroupPermissions($groupId, $resourceId);
            if (isset($groupPermissions[$action]) && $groupPermissions[$action]) {
                $this->logger->log("Group permission granted: User $userId via group $groupId to resource $resourceId");
                return true;
            }
        }

        // Verificar regras de negócio específicas
        switch ($action) {
            case 'view':
                // Verificar se o usuário tem permissão para visualizar o tipo de recurso
                $resourceType = $resource['type'];
                if ($this->canUserAccessResourceType($userId, $resourceType, 'view')) {
                    return true;
                }
                break;

            case 'edit':
                // Verificar se o usuário tem um plano que permite edição
                if ($this->hasUserPremiumPlan($userId)) {
                    return true;
                }
                break;

            case 'delete':
                // Apenas o proprietário e administradores podem excluir
                return false;

            default:
                // Permissão desconhecida
                return false;
        }

        // Registrar a negação de acesso
        $this->logger->log("Access denied: $userId to resource $resourceId for action $action");

        return false;
    }
}
