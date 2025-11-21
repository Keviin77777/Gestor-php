<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Sidebar Admin</title>
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 300px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .nav-group {
            margin-bottom: 1rem;
        }
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        .nav-item:hover {
            background: #e9ecef;
        }
        .nav-item.has-submenu {
            justify-content: space-between;
        }
        .submenu-arrow {
            width: 16px;
            height: 16px;
            transition: transform 0.3s;
        }
        .nav-item.active .submenu-arrow {
            transform: rotate(180deg);
        }
        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            opacity: 0;
            padding-left: 1rem;
        }
        .submenu.expanded {
            max-height: 300px;
            opacity: 1;
        }
        .submenu-item {
            display: block;
            padding: 0.5rem 1rem;
            color: #666;
            text-decoration: none;
            border-radius: 4px;
            margin: 0.25rem 0;
        }
        .submenu-item:hover {
            background: #f8f9fa;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h3>Teste Menu Administração</h3>
        
        <div class="nav-group">
            <a href="#" class="nav-item has-submenu" onclick="toggleSubmenu(event, 'admin-submenu')">
                <span>Administração</span>
                <svg class="submenu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </a>
            <div class="submenu" id="admin-submenu">
                <a href="/admin/resellers" class="submenu-item">Revendedores</a>
                <a href="/admin/reseller-plans" class="submenu-item">Planos de Revendedores</a>
                <a href="/admin/payment-history" class="submenu-item">Histórico de Pagamentos</a>
            </div>
        </div>
        
        <div class="nav-group">
            <a href="#" class="nav-item has-submenu" onclick="toggleSubmenu(event, 'clients-submenu')">
                <span>Clientes</span>
                <svg class="submenu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </a>
            <div class="submenu" id="clients-submenu">
                <a href="/clients" class="submenu-item">Lista de Clientes</a>
                <a href="/plans" class="submenu-item">Planos</a>
                <a href="/applications" class="submenu-item">Aplicativos</a>
            </div>
        </div>
    </div>

    <script>
        function toggleSubmenu(event, submenuId) {
            event.preventDefault();
            console.log('toggleSubmenu chamado:', submenuId);
            
            const submenu = document.getElementById(submenuId);
            const parentItem = event.currentTarget;
            
            console.log('Submenu encontrado:', submenu);
            console.log('Parent item:', parentItem);
            
            if (submenu.classList.contains('expanded')) {
                console.log('Fechando submenu');
                submenu.classList.remove('expanded');
                parentItem.classList.remove('active');
            } else {
                console.log('Abrindo submenu');
                // Fechar outros submenus
                document.querySelectorAll('.submenu.expanded').forEach(menu => {
                    menu.classList.remove('expanded');
                });
                document.querySelectorAll('.nav-item.has-submenu.active').forEach(item => {
                    if (item !== parentItem) {
                        item.classList.remove('active');
                    }
                });
                
                // Abrir o submenu clicado
                submenu.classList.add('expanded');
                parentItem.classList.add('active');
            }
        }
        
        console.log('Script carregado com sucesso');
    </script>
</body>
</html>
