<?php
/**
 * MikroTik RouterOS 7 REST API Integration Service
 */
class MikroTikRouter {
    private $ip;
    private $port;
    private $user;
    private $pass;
    private $baseUrl;

    public function __construct(string $ip, int $port, string $user, string $pass) {
        $this->ip   = $ip;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->baseUrl = "https://{$ip}:{$port}/rest";
    }

    /**
     * Cria ou atualiza um usuário no Hotspot do MikroTik
     * 
     * @return array ['ok' => bool, 'message' => string, 'action' => string]
     */
    public function criarOuAtualizarUsuario(string $cpf, string $senha, string $profile): array {
        // 1. Verificar se usuário já existe
        $check = $this->request('GET', "/ip/hotspot/user?name=" . urlencode($cpf));
        
        if (!$check['success']) {
            return [
                'ok'      => false,
                'message' => $check['error'] ?? 'Erro desconhecido na conexão',
                'action'  => 'error'
            ];
        }

        $existingUser = json_decode($check['body'], true);

        if (!empty($existingUser) && is_array($existingUser) && isset($existingUser[0]['.id'])) {
            // 2. Usuário existe: Atualizar senha
            $id = $existingUser[0]['.id'];
            $update = $this->request('PATCH', "/ip/hotspot/user/{$id}", [
                'password' => $senha,
                'profile'  => $profile
            ]);

            if ($update['success']) {
                return ['ok' => true, 'message' => 'Usuário atualizado', 'action' => 'updated'];
            }
        } else {
            // 3. Usuário não existe: Criar novo
            $create = $this->request('PUT', "/ip/hotspot/user", [
                'name'     => $cpf,
                'password' => $senha,
                'profile'  => $profile
            ]);

            if ($create['success']) {
                return ['ok' => true, 'message' => 'Usuário criado', 'action' => 'created'];
            }
        }

        return ['ok' => false, 'message' => 'Falha na operação MikroTik', 'action' => 'error'];
    }

    /**
     * Helper para requisições cURL
     */
    private function request(string $method, string $path, array $data = null): array {
        $ch = curl_init($this->baseUrl . $path);
        
        $options = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => "{$this->user}:{$this->pass}",
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json']
        ];

        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);
        
        $body  = curl_exec($ch);
        $error = curl_error($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            $this->logError("Erro cURL: $error");
            return ['success' => false, 'error' => $error];
        }

        if ($code >= 400) {
            $this->logError("Erro API ($code): $body");
            return ['success' => false, 'error' => "HTTP $code"];
        }

        return ['success' => true, 'body' => $body];
    }

    private function logError(string $message) {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $file = $logDir . '/router_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($file, "[{$timestamp}] IP:{$this->ip} - {$message}\n", FILE_APPEND);
    }
}
