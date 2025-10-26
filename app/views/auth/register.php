<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>UltraGestor</h1>
                <p>Crie sua conta e ganhe 3 dias grátis</p>
            </div>
            
            <form id="registerForm" class="auth-form">
                <div class="form-group">
                    <label for="name">Nome Completo</label>
                    <input type="text" id="name" name="name" required autocomplete="name">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="whatsapp">WhatsApp (opcional)</label>
                    <input type="tel" id="whatsapp" name="whatsapp" placeholder="(00) 00000-0000" autocomplete="tel">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password" minlength="6">
                    <small style="color: #64748b; font-size: 0.85rem;">Mínimo 6 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirmation">Confirmar Senha</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Criar Conta Grátis
                </button>
                
                <div class="form-footer">
                    <p>Já tem uma conta? <a href="/login" class="link">Faça login</a></p>
                </div>
            </form>
            
            <div id="message" class="message"></div>
        </div>
    </div>
    
    <script src="/assets/js/auth.js"></script>
    <script>
        // Adicionar handler de registro
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', handleRegister);
        }
        
        async function handleRegister(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const whatsapp = document.getElementById('whatsapp').value;
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;
            const submitBtn = registerForm.querySelector('button[type="submit"]');
            
            // Validação
            if (!name || !email || !password) {
                showMessage('Preencha todos os campos obrigatórios', 'error');
                return;
            }
            
            if (password !== passwordConfirmation) {
                showMessage('As senhas não coincidem', 'error');
                return;
            }
            
            if (password.length < 6) {
                showMessage('A senha deve ter no mínimo 6 caracteres', 'error');
                return;
            }
            
            // Loading
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            hideMessage();
            
            try {
                const response = await fetch(`${API_URL}/api/auth/register`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name, email, whatsapp, password })
                });
                
                const data = await response.json();
                
                if (response.ok && data.token) {
                    // Salvar token
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    
                    // Redirecionar
                    showMessage('Conta criada com sucesso! Redirecionando...', 'success');
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 1000);
                } else if (data.errors) {
                    // Erros de validação
                    const errorMessages = Object.values(data.errors).join(', ');
                    showMessage(errorMessages, 'error');
                } else {
                    showMessage(data.error || 'Erro ao criar conta', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showMessage('Erro ao conectar com o servidor', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
            }
        }
        
        // Máscara de telefone
        const whatsappInput = document.getElementById('whatsapp');
        if (whatsappInput) {
            whatsappInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) value = value.slice(0, 11);
                
                if (value.length > 6) {
                    value = value.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');
                } else if (value.length > 2) {
                    value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
                } else if (value.length > 0) {
                    value = value.replace(/^(\d*)/, '($1');
                }
                
                e.target.value = value;
            });
        }
    </script>
</body>
</html>
