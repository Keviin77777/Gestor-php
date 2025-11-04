<!-- TESTE HEADER MENU -->
<div style="background: #1a1f2e; padding: 1rem; border-bottom: 2px solid #f00; color: white; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <strong>HEADER MENU TESTE</strong> - Se você está vendo isso, o include funciona!
    </div>
    <div>
        Usuário: <?= $_SESSION['user']['name'] ?? 'Desconhecido' ?>
    </div>
</div>
