/**
 * Validação de Senha IPTV para Sigma
 * Valida senhas de acordo com as regras do Sigma quando o servidor tem integração configurada
 */

// Variável global para armazenar se o servidor selecionado tem Sigma
let selectedServerHasSigma = false;

/**
 * Verificar se o servidor tem Sigma configurado
 */
async function checkServerHasSigma(serverName) {
    if (!serverName) {
        selectedServerHasSigma = false;
        updatePasswordValidationUI(false);
        return false;
    }
    
    try {
        // Buscar informações do servidor
        const server = availableServers.find(s => s.name === serverName);
        
        if (server && server.sigma_token && server.sigma_token.trim() !== '') {
            selectedServerHasSigma = true;
            updatePasswordValidationUI(true);
            return true;
        } else {
            selectedServerHasSigma = false;
            updatePasswordValidationUI(false);
            return false;
        }
    } catch (error) {
        // Erro silencioso
        selectedServerHasSigma = false;
        updatePasswordValidationUI(false);
        return false;
    }
}

/**
 * Atualizar UI de validação de senha
 */
function updatePasswordValidationUI(hasSigma) {
    // Desktop
    const passwordGroupDesktop = document.querySelector('#clientIptvPassword')?.closest('.modern-form-group');
    // Mobile
    const passwordGroupMobile = document.querySelector('#clientIptvPasswordMobile')?.closest('.modern-form-group');
    
    const validationHTML = hasSigma ? `
        <div class="password-validation-hint" style="margin-top: 0.5rem; padding: 0.75rem; background: rgba(255, 193, 7, 0.1); border-left: 3px solid var(--warning); border-radius: 4px; font-size: 0.8125rem; line-height: 1.5;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; flex-shrink: 0;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <strong style="color: var(--text-primary);">Servidor com Sigma - Regras de Senha:</strong>
            </div>
            <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary);">
                <li>O campo usuário só pode conter letras, números e traços.</li>
                <li>A senha deve conter apenas letras e números e ter no mínimo 9 caracteres.</li>
                <li>A senha precisa ter no mínimo 8 caracteres.</li>
            </ul>
        </div>
    ` : '';
    
    // Remover hints anteriores
    if (passwordGroupDesktop) {
        const oldHint = passwordGroupDesktop.querySelector('.password-validation-hint');
        if (oldHint) oldHint.remove();
        
        if (hasSigma) {
            passwordGroupDesktop.insertAdjacentHTML('beforeend', validationHTML);
        }
    }
    
    if (passwordGroupMobile) {
        const oldHint = passwordGroupMobile.querySelector('.password-validation-hint');
        if (oldHint) oldHint.remove();
        
        if (hasSigma) {
            passwordGroupMobile.insertAdjacentHTML('beforeend', validationHTML);
        }
    }
}

/**
 * Validar senha IPTV de acordo com as regras do Sigma
 */
function validateIptvPassword(password) {
    if (!selectedServerHasSigma) {
        // Se não tem Sigma, não precisa validar
        return { valid: true, message: '' };
    }
    
    if (!password || password.trim() === '') {
        // Senha vazia é permitida (campo opcional)
        return { valid: true, message: '' };
    }
    
    // Regras do Sigma:
    // 1. Mínimo 8 caracteres (regra base)
    if (password.length < 8) {
        return {
            valid: false,
            message: 'A senha precisa ter no mínimo 8 caracteres.'
        };
    }
    
    // 2. Apenas letras e números (sem caracteres especiais)
    const onlyAlphanumericRegex = /^[a-zA-Z0-9]+$/;
    if (!onlyAlphanumericRegex.test(password)) {
        return {
            valid: false,
            message: 'A senha só pode conter letras e números (sem caracteres especiais).'
        };
    }
    
    // 3. Deve conter pelo menos uma letra e um número
    const hasLetter = /[a-zA-Z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    
    if (!hasLetter || !hasNumber) {
        return {
            valid: false,
            message: 'A senha deve conter pelo menos uma letra e um número.'
        };
    }
    
    // 4. Se tiver letras e números, precisa ter no mínimo 9 caracteres
    if (hasLetter && hasNumber && password.length < 9) {
        return {
            valid: false,
            message: 'A senha deve ter no mínimo 9 caracteres quando contém letras e números.'
        };
    }
    
    return { valid: true, message: '' };
}

/**
 * Mostrar erro de validação
 */
function showPasswordValidationError(message, inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    // Adicionar classe de erro
    input.classList.add('field-error');
    
    // Remover erro anterior se existir
    const formGroup = input.closest('.modern-form-group');
    const oldError = formGroup?.querySelector('.password-error-message');
    if (oldError) oldError.remove();
    
    // Adicionar mensagem de erro
    if (formGroup && message) {
        const errorHTML = `
            <small class="password-error-message" style="color: var(--danger); font-size: 0.8125rem; margin-top: 0.25rem; display: block;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                ${message}
            </small>
        `;
        formGroup.insertAdjacentHTML('beforeend', errorHTML);
    }
    
    // Focar no campo
    input.focus();
}

/**
 * Limpar erro de validação
 */
function clearPasswordValidationError(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.classList.remove('field-error');
    
    const formGroup = input.closest('.modern-form-group');
    const errorMessage = formGroup?.querySelector('.password-error-message');
    if (errorMessage) errorMessage.remove();
}

/**
 * Configurar validação em tempo real
 */
function setupPasswordValidation() {
    // Desktop
    const passwordInputDesktop = document.getElementById('clientIptvPassword');
    const serverSelectDesktop = document.getElementById('clientServer');
    
    if (passwordInputDesktop) {
        // Remover listeners antigos clonando o elemento
        const newPasswordInput = passwordInputDesktop.cloneNode(true);
        passwordInputDesktop.parentNode.replaceChild(newPasswordInput, passwordInputDesktop);
        
        newPasswordInput.addEventListener('input', function() {
            clearPasswordValidationError('clientIptvPassword');
        });
        
        newPasswordInput.addEventListener('blur', function() {
            if (selectedServerHasSigma && this.value.trim() !== '') {
                const validation = validateIptvPassword(this.value);
                if (!validation.valid) {
                    showPasswordValidationError(validation.message, 'clientIptvPassword');
                }
            }
        });
    }
    
    if (serverSelectDesktop) {
        // Remover listeners antigos clonando o elemento
        const newServerSelect = serverSelectDesktop.cloneNode(true);
        serverSelectDesktop.parentNode.replaceChild(newServerSelect, serverSelectDesktop);
        
        newServerSelect.addEventListener('change', function() {
            checkServerHasSigma(this.value);
            // Revalidar senha se já tiver algo digitado
            const passwordInput = document.getElementById('clientIptvPassword');
            if (passwordInput && passwordInput.value.trim() !== '') {
                clearPasswordValidationError('clientIptvPassword');
            }
        });
    }
    
    // Mobile
    const passwordInputMobile = document.getElementById('clientIptvPasswordMobile');
    const serverSelectMobile = document.getElementById('clientServerMobile');
    
    if (passwordInputMobile) {
        // Remover listeners antigos clonando o elemento
        const newPasswordInputMobile = passwordInputMobile.cloneNode(true);
        passwordInputMobile.parentNode.replaceChild(newPasswordInputMobile, passwordInputMobile);
        
        newPasswordInputMobile.addEventListener('input', function() {
            clearPasswordValidationError('clientIptvPasswordMobile');
        });
        
        newPasswordInputMobile.addEventListener('blur', function() {
            if (selectedServerHasSigma && this.value.trim() !== '') {
                const validation = validateIptvPassword(this.value);
                if (!validation.valid) {
                    showPasswordValidationError(validation.message, 'clientIptvPasswordMobile');
                }
            }
        });
    }
    
    if (serverSelectMobile) {
        // Remover listeners antigos clonando o elemento
        const newServerSelectMobile = serverSelectMobile.cloneNode(true);
        serverSelectMobile.parentNode.replaceChild(newServerSelectMobile, serverSelectMobile);
        
        newServerSelectMobile.addEventListener('change', function() {
            checkServerHasSigma(this.value);
            // Revalidar senha se já tiver algo digitado
            const passwordInput = document.getElementById('clientIptvPasswordMobile');
            if (passwordInput && passwordInput.value.trim() !== '') {
                clearPasswordValidationError('clientIptvPasswordMobile');
            }
        });
    }
}

/**
 * Validar antes de salvar (Desktop)
 */
function validatePasswordBeforeSaveDesktop() {
    if (!selectedServerHasSigma) {
        return true;
    }
    
    const passwordInput = document.getElementById('clientIptvPassword');
    if (!passwordInput) return true;
    
    const password = passwordInput.value.trim();
    if (password === '') {
        // Senha vazia é permitida
        return true;
    }
    
    const validation = validateIptvPassword(password);
    if (!validation.valid) {
        showPasswordValidationError(validation.message, 'clientIptvPassword');
        return false;
    }
    
    return true;
}

/**
 * Validar antes de salvar (Mobile)
 */
function validatePasswordBeforeSaveMobile() {
    if (!selectedServerHasSigma) {
        return true;
    }
    
    const passwordInput = document.getElementById('clientIptvPasswordMobile');
    if (!passwordInput) return true;
    
    const password = passwordInput.value.trim();
    if (password === '') {
        // Senha vazia é permitida
        return true;
    }
    
    const validation = validateIptvPassword(password);
    if (!validation.valid) {
        showPasswordValidationError(validation.message, 'clientIptvPasswordMobile');
        return false;
    }
    
    return true;
}

// Inicializar validação quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupPasswordValidation);
} else {
    setupPasswordValidation();
}
