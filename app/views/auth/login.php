<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>UltraGestor</h1>
                <p>Sistema de Gestão IPTV</p>
            </div>
            
            <form id="loginForm" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                
                <div class="form-actions">
                    <a href="/forgot-password" class="link">Esqueceu a senha?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Entrar
                </button>
                
                <div class="form-footer">
                    <p>Não tem uma conta? <a href="/register" class="link">Registre-se</a></p>
                </div>
            </form>
            
            <div id="message" class="message"></div>
        </div>
    </div>
    
    <script src="/assets/js/auth.js"></script>
</body>
</html>
