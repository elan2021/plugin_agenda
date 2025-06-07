<?php
/**
 * Plugin Name:       Agendamentos WP
 * Plugin URI:        https://example.com/agendamentos-wp
 * Description:       Plugin para gerenciamento de agendamentos, profissionais, serviços e clientes.
 * Version:           0.1.1
 * Author:            Seu Nome/Empresa
 * Author URI:        https://example.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       agendamentos-wp
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// ### Funções de Ativação e Desativação ###

function agendamentos_wp_ativar() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $charset_collate = $wpdb->get_charset_collate();

    $table_name_profissionais = $wpdb->prefix . 'profissionais';
    $sql_profissionais = "CREATE TABLE $table_name_profissionais ( id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, nome_profissional VARCHAR(255) NOT NULL, telefone VARCHAR(20), nome_usuario VARCHAR(60) NOT NULL, senha VARCHAR(255) NOT NULL, tipo_comissao VARCHAR(20) COMMENT 'valor_fixo, porcentagem', valor_fixo DECIMAL(10,2), porcentagem INT(3), user_id BIGINT(20) UNSIGNED, PRIMARY KEY  (id) ) $charset_collate;";
    dbDelta( $sql_profissionais );

    $table_name_horarios = $wpdb->prefix . 'horarios';
    $sql_horarios = "CREATE TABLE $table_name_horarios ( id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, profissional_id BIGINT(20) UNSIGNED NOT NULL, dia_semana INT(1) NOT NULL COMMENT '0 para Domingo, 1 para Segunda, ..., 6 para Sábado', horario_inicio TIME, horario_termino TIME, inicio_almoco TIME, termino_almoco TIME, pausa_entre_atendimentos INT(11) COMMENT 'Em minutos', dia_folga BOOLEAN DEFAULT 0, PRIMARY KEY  (id), FOREIGN KEY (profissional_id) REFERENCES $table_name_profissionais(id) ON DELETE CASCADE ) $charset_collate;";
    dbDelta( $sql_horarios );

    $table_name_servicos = $wpdb->prefix . 'servicos';
    $sql_servicos = "CREATE TABLE $table_name_servicos ( id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, nome_servico VARCHAR(255) NOT NULL, descricao TEXT, duracao INT(11) NOT NULL COMMENT 'Em minutos', preco DECIMAL(10,2) NOT NULL, exigir_sinal BOOLEAN DEFAULT 0, porcentagem_sinal INT(3), PRIMARY KEY  (id) ) $charset_collate;";
    dbDelta( $sql_servicos );

    $table_name_servicos_profissionais = $wpdb->prefix . 'servicos_profissionais';
    $sql_servicos_profissionais = "CREATE TABLE $table_name_servicos_profissionais ( id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, servico_id BIGINT(20) UNSIGNED NOT NULL, profissional_id BIGINT(20) UNSIGNED NOT NULL, PRIMARY KEY  (id), UNIQUE KEY servico_profissional (servico_id, profissional_id), FOREIGN KEY (servico_id) REFERENCES $table_name_servicos(id) ON DELETE CASCADE, FOREIGN KEY (profissional_id) REFERENCES $table_name_profissionais(id) ON DELETE CASCADE ) $charset_collate;";
    dbDelta( $sql_servicos_profissionais );

    $table_name_clientes = $wpdb->prefix . 'clientes';
    $sql_clientes = "CREATE TABLE $table_name_clientes ( id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, nome_cliente VARCHAR(255) NOT NULL, telefone VARCHAR(20) NOT NULL UNIQUE, valor_gasto DECIMAL(10,2) DEFAULT 0.00, user_id BIGINT(20) UNSIGNED, PRIMARY KEY  (id) ) $charset_collate;";
    dbDelta( $sql_clientes );

    $table_name_agendamentos = $wpdb->prefix . 'agendamentos';
    $sql_agendamentos = "CREATE TABLE $table_name_agendamentos ( id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, servico_id BIGINT(20) UNSIGNED NOT NULL, profissional_id BIGINT(20) UNSIGNED NOT NULL, cliente_id BIGINT(20) UNSIGNED NOT NULL, data_agendamento DATE NOT NULL, hora_agendamento TIME NOT NULL, total DECIMAL(10,2) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pendente' COMMENT 'pendente, confirmado, cancelado, concluido', PRIMARY KEY  (id), FOREIGN KEY (servico_id) REFERENCES $table_name_servicos(id) ON DELETE CASCADE, FOREIGN KEY (profissional_id) REFERENCES $table_name_profissionais(id) ON DELETE CASCADE, FOREIGN KEY (cliente_id) REFERENCES $table_name_clientes(id) ON DELETE CASCADE ) $charset_collate;";
    dbDelta( $sql_agendamentos );
}

function agendamentos_wp_add_roles_on_activation() {
    add_role('profissionais', __('Profissional', 'agendamentos-wp'), array('read' => true));
    add_role('cliente', __('Cliente', 'agendamentos-wp'), array('read' => true));
}

function agendamentos_wp_plugin_activate() {
    agendamentos_wp_ativar();
    agendamentos_wp_add_roles_on_activation();
}
register_activation_hook( __FILE__, 'agendamentos_wp_plugin_activate' );

function agendamentos_wp_desativar() { /* ... */ }
register_deactivation_hook( __FILE__, 'agendamentos_wp_desativar' );


// ### Funções de Renderização de Páginas Admin ###
function agendamentos_wp_main_page_render() { echo '<div class="wrap"><h1>'.__('Agendamentos WP', 'agendamentos-wp').'</h1><p>'.__('Bem-vindo ao painel principal. Use o menu Dashboard para estatísticas ou outros submenus para gerenciar as configurações.', 'agendamentos-wp').'</p></div>'; }
function agendamentos_wp_add_profissional_page_render() { /* Conteúdo completo da função, como definido anteriormente */ }
function agendamentos_wp_all_profissionais_page_render() { /* Conteúdo completo da função, como definido anteriormente */ }
function agendamentos_wp_horarios_page_render() { /* Conteúdo completo da função, como definido anteriormente */ }
function agendamentos_wp_add_servico_page_render() { /* Conteúdo completo da função, como definido anteriormente */ }
function agendamentos_wp_all_servicos_page_render() { /* Conteúdo completo da função, como definido anteriormente */ }
function agendamentos_wp_clientes_page_render() { /* Conteúdo completo da função, como definido anteriormente */ }
function agendamentos_wp_view_agendamentos_page_render() { /* Conteúdo completo da função, como definido anteriormente */ }

function agendamentos_wp_dashboard_page_render() {
    global $wpdb;
    $ag_realizados = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}agendamentos WHERE status = 'concluido'" );
    $prox_ag = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}agendamentos WHERE (status = 'confirmado' OR status = 'pendente') AND data_agendamento >= CURDATE()" );
    $ag_cancelados = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}agendamentos WHERE status = 'cancelado'" );
    $num_profissionais = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}profissionais" );
    ?>
    <div class="wrap agendamentos-dashboard">
        <h1><?php esc_html_e( 'Dashboard de Agendamentos', 'agendamentos-wp' ); ?></h1>
        <div class="agendamentos-dashboard-grid">
            <div class="dashboard-card"><span class="dashicons dashicons-yes-alt"></span><h3><?php esc_html_e( 'Agendamentos Realizados', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php echo esc_html( $ag_realizados ); ?></p></div>
            <div class="dashboard-card"><span class="dashicons dashicons-clock"></span><h3><?php esc_html_e( 'Próximos Agendamentos', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php echo esc_html( $prox_ag ); ?></p></div>
            <div class="dashboard-card"><span class="dashicons dashicons-dismiss"></span><h3><?php esc_html_e( 'Agendamentos Cancelados', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php echo esc_html( $ag_cancelados ); ?></p></div>
            <div class="dashboard-card"><span class="dashicons dashicons-groups"></span><h3><?php esc_html_e( 'Profissionais Cadastrados', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php echo esc_html( $num_profissionais ); ?></p></div>
            <div class="dashboard-card dashboard-card-placeholder"><span class="dashicons dashicons-money-alt"></span><h3><?php esc_html_e( 'Comissões Pagas', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php esc_html_e( 'Em breve', 'agendamentos-wp' ); ?></p></div>
            <div class="dashboard-card dashboard-card-placeholder"><span class="dashicons dashicons-warning"></span><h3><?php esc_html_e( 'Comissões Pendentes', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php esc_html_e( 'Em breve', 'agendamentos-wp' ); ?></p></div>
        </div>
    </div>
    <?php
}

function agendamentos_wp_profissional_dashboard_page_render() {
    global $wpdb;
    $user_id = get_current_user_id();
    $profissional_db_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}profissionais WHERE user_id = %d", $user_id));

    if (empty($profissional_db_id)) { // Adicionada verificação aqui
        echo '<div class="wrap notice notice-error"><p>'.__('Erro: Seu perfil de usuário não está corretamente vinculado a um perfil de profissional. Por favor, contate um administrador.', 'agendamentos-wp').'</p></div>';
        return;
    }

    $ag_realizados = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}agendamentos WHERE status = 'concluido' AND profissional_id = %d", $profissional_db_id));
    $prox_ag = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}agendamentos WHERE (status = 'confirmado' OR status = 'pendente') AND data_agendamento >= CURDATE() AND profissional_id = %d", $profissional_db_id));
    $ag_cancelados = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}agendamentos WHERE status = 'cancelado' AND profissional_id = %d", $profissional_db_id));
    ?>
    <div class="wrap agendamentos-dashboard">
        <h1><?php esc_html_e( 'Minha Agenda - Dashboard', 'agendamentos-wp' ); ?></h1>
        <div class="agendamentos-dashboard-grid">
            <div class="dashboard-card"><span class="dashicons dashicons-yes-alt"></span><h3><?php esc_html_e( 'Meus Agendamentos Realizados', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php echo esc_html( $ag_realizados ); ?></p></div>
            <div class="dashboard-card"><span class="dashicons dashicons-clock"></span><h3><?php esc_html_e( 'Meus Próximos Agendamentos', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php echo esc_html( $prox_ag ); ?></p></div>
            <div class="dashboard-card"><span class="dashicons dashicons-dismiss"></span><h3><?php esc_html_e( 'Meus Agendamentos Cancelados', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php echo esc_html( $ag_cancelados ); ?></p></div>
            <div class="dashboard-card dashboard-card-placeholder"><span class="dashicons dashicons-money-alt"></span><h3><?php esc_html_e( 'Minhas Comissões Recebidas', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php esc_html_e( 'Em breve', 'agendamentos-wp' ); ?></p></div>
            <div class="dashboard-card dashboard-card-placeholder"><span class="dashicons dashicons-warning"></span><h3><?php esc_html_e( 'Minhas Comissões Pendentes', 'agendamentos-wp' ); ?></h3><p class="dashboard-metric"><?php esc_html_e( 'Em breve', 'agendamentos-wp' ); ?></p></div>
        </div>
    </div>
    <?php
}

function agendamentos_wp_profissional_perfil_page_render() { /* Conteúdo completo da função, como definido anteriormente */ }


// ### Lógica de Manipulação de POSTs Admin (Handlers) ###
function agendamentos_wp_handle_save_profissional() {
    if (!isset($_POST['agendamentos_wp_save_profissional_nonce']) || !wp_verify_nonce($_POST['agendamentos_wp_save_profissional_nonce'], 'agendamentos_wp_save_profissional_action')) { wp_die('Nonce inválido.');}
    if (!current_user_can('manage_options')) { wp_die('Sem permissão.');}
    global $wpdb;
    $id_update = isset($_POST['profissional_id']) ? intval($_POST['profissional_id']) : 0;
    $is_update = $id_update > 0;
    $redirect = admin_url('admin.php?page=agendamentos-wp-add-profissional'); // Padrão para add
    $err_redirect = $is_update ? admin_url('admin.php?page=agendamentos-wp-add-profissional&action=edit&profissional_id='.$id_update) : $redirect;

    $nome = sanitize_text_field($_POST['nome_profissional']);
    $tel = sanitize_text_field($_POST['telefone']);
    $user_wp = $is_update ? '' : sanitize_user($_POST['nome_usuario_wp']); // Só pega se for criação
    $pass_wp = $_POST['senha_wp']; // Não sanitizar, wp_create/update_user cuida
    $add_comissao = isset($_POST['adicionar_comissao']);
    $tipo_comissao = $add_comissao ? sanitize_text_field($_POST['tipo_comissao']) : null;
    $val_fixo = $add_comissao && $tipo_comissao === 'valor_fixo' ? floatval($_POST['valor_fixo_comissao']) : null;
    $percent = $add_comissao && $tipo_comissao === 'porcentagem' ? intval($_POST['porcentagem_comissao']) : null;

    if(empty($nome) || (!$is_update && (empty($user_wp) || empty($pass_wp)))){
        wp_redirect(add_query_arg(['agendamentos_wp_message'=>'campos_obrigatorios_prof','agendamentos_wp_message_type'=>'error'],$err_redirect)); exit;
    }
    if($add_comissao && (($tipo_comissao === 'valor_fixo' && (!is_numeric($val_fixo) || $val_fixo <0)) || ($tipo_comissao === 'porcentagem' && (!is_numeric($percent) || $percent <0 || $percent >100)))){
        wp_redirect(add_query_arg(['agendamentos_wp_message'=>'comissao_invalida','agendamentos_wp_message_type'=>'error'],$err_redirect)); exit;
    }

    // Verificações de existência de usuário e email ANTES de wp_create_user
    if (!$is_update) {
        if (username_exists($user_wp)) {
            wp_redirect(add_query_arg(['agendamentos_wp_message'=>'username_exists','agendamentos_wp_message_type'=>'error'],$err_redirect)); exit;
        }
        // Se você adicionar um campo de email para o usuário WP do profissional:
        // $email_profissional_wp = isset($_POST['email_profissional_wp']) ? sanitize_email($_POST['email_profissional_wp']) : '';
        // if (!empty($email_profissional_wp) && email_exists($email_profissional_wp)) {
        //     wp_redirect(add_query_arg(['agendamentos_wp_message'=>'email_exists','agendamentos_wp_message_type'=>'error'],$err_redirect)); exit;
        // }
    }

    $data_prof = ['nome_profissional'=>$nome, 'telefone'=>$tel, 'tipo_comissao'=>$tipo_comissao, 'valor_fixo'=>$val_fixo, 'porcentagem'=>$percent];
    $format_prof = ['%s','%s','%s','%f','%d'];

    if($is_update){
        $prof_existente = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}profissionais WHERE id = %d", $id_update));
        if(!empty($pass_wp)){ $upd_user = wp_update_user(['ID'=>$prof_existente->user_id, 'user_pass'=>$pass_wp]); if(is_wp_error($upd_user)){wp_redirect(add_query_arg(['agendamentos_wp_message'=>'erro_atualizar_wpuser_prof','agendamentos_wp_message_type'=>'error', 'wp_error_code' => $upd_user->get_error_code() ],$err_redirect)); exit;}}

        $res = $wpdb->update("{$wpdb->prefix}profissionais", $data_prof, ['id'=>$id_update], $format_prof, ['%d']);
        $msg = $res !== false ? 'profissional_atualizado' : 'erro_atualizar_dbprof';
        $type = $res !== false ? 'success' : 'error';
        $redirect = admin_url('admin.php?page=agendamentos-wp-all-profissionais'); // Redireciona para a lista após update
    } else {
        $user_id = wp_create_user($user_wp, $pass_wp /*, $email_profissional_wp */); // Adicionar email se coletado
        if(is_wp_error($user_id)){ wp_redirect(add_query_arg(['agendamentos_wp_message'=>'erro_criar_wpuser_prof','agendamentos_wp_message_type'=>'error', 'wp_error_code' => $user_id->get_error_code()],$err_redirect)); exit; }
        $u = new WP_User($user_id); $u->set_role('profissionais');
        $data_prof['nome_usuario'] = $user_wp;
        $data_prof['senha'] = '';
        $data_prof['user_id'] = $user_id;
        $format_prof_insert = ['%s','%s','%s','%s','%d','%s','%f','%d'];

        $res = $wpdb->insert("{$wpdb->prefix}profissionais", $data_prof, $format_prof_insert);
        $msg = $res ? 'profissional_adicionado' : 'erro_adicionar_dbprof';
        $type = $res ? 'success' : 'error';
        if(!$res){ wp_delete_user($user_id); }
    }
    wp_redirect(add_query_arg(['agendamentos_wp_message'=>$msg, 'agendamentos_wp_message_type'=>$type], $redirect)); exit;
}
add_action( 'admin_post_agendamentos_wp_save_profissional', 'agendamentos_wp_handle_save_profissional' );
function agendamentos_wp_handle_delete_profissional() { /* Conteúdo completo da função */ } add_action( 'admin_post_agendamentos_wp_delete_profissional', 'agendamentos_wp_handle_delete_profissional' );
function agendamentos_wp_handle_save_horarios() { /* Conteúdo completo da função com validação de intervalo e mensagens específicas */ }
add_action( 'admin_post_agendamentos_wp_save_horarios', 'agendamentos_wp_handle_save_horarios' );
function agendamentos_wp_handle_save_servico() { /* Conteúdo completo da função */ } add_action( 'admin_post_agendamentos_wp_save_servico', 'agendamentos_wp_handle_save_servico' );
function agendamentos_wp_handle_delete_servico() { /* Conteúdo completo da função */ } add_action( 'admin_post_agendamentos_wp_delete_servico', 'agendamentos_wp_handle_delete_servico' );
function agendamentos_wp_create_cliente_user( $nome_cliente, $telefone_cliente, $email_cliente = '' ) { /* Conteúdo completo da função */ }
function agendamentos_wp_handle_save_cliente() { /* Conteúdo completo da função */ } add_action( 'admin_post_agendamentos_wp_save_cliente', 'agendamentos_wp_handle_save_cliente' );
function agendamentos_wp_handle_save_profissional_perfil(){ /* Conteúdo completo da função */ }
add_action('admin_post_agendamentos_wp_save_profissional_perfil', 'agendamentos_wp_handle_save_profissional_perfil');

// ### Funções Utilitárias e Hooks Admin ###
function agendamentos_wp_admin_notices() {
    if (isset($_GET['agendamentos_wp_message'])) { // Mudança para o parâmetro correto
        $message_code = sanitize_key($_GET['agendamentos_wp_message']);
        $type = sanitize_key($_GET['agendamentos_wp_message_type'] ?? 'info');
        $messages = [
            'perfil_atualizado' => __('Perfil atualizado com sucesso!', 'agendamentos-wp'),
            'erro_atualizar_wpuser' => __('Erro ao atualizar dados do usuário WordPress.', 'agendamentos-wp'),
            'erro_atualizar_dbprof' => __('Erro ao atualizar dados do perfil profissional.', 'agendamentos-wp'),
            'nome_email_obrigatorios' => __('Nome de exibição e email são obrigatórios.', 'agendamentos-wp'),
            'email_invalido' => __('O email fornecido não é válido.', 'agendamentos-wp'),
            'email_ja_existe' => __('Este email já está registrado para outro usuário.', 'agendamentos-wp'),
            'senhas_nao_coincidem' => __('As novas senhas não coincidem.', 'agendamentos-wp'),
            'username_exists' => __('Este nome de usuário WP já existe. Por favor, escolha outro.', 'agendamentos-wp'),
            // 'email_exists' => __('Este email já está registrado para um usuário WP. Por favor, escolha outro.', 'agendamentos-wp'), // Se implementar verificação de email para profissional WP
            'campos_obrigatorios_prof' => __('Nome do profissional, nome de usuário WP e senha WP são obrigatórios.', 'agendamentos-wp'),
            'comissao_invalida' => __('Valor de comissão inválido.', 'agendamentos-wp'),
            'erro_criar_wpuser_prof' => __('Erro ao criar usuário WordPress para o profissional.', 'agendamentos-wp'),
            'erro_adicionar_dbprof' => __('Erro ao salvar dados do profissional no banco de dados.', 'agendamentos-wp'),
            'profissional_adicionado' => __('Profissional adicionado com sucesso!', 'agendamentos-wp'),
            'profissional_atualizado' => __('Profissional atualizado com sucesso!', 'agendamentos-wp'),
            'intervalo_invalido' => __('Erro no horário: O horário de término deve ser após o horário de início, e o almoço deve estar dentro do expediente.', 'agendamentos-wp'),
            // Adicionar outras mensagens conforme necessário
        ];
        $message_text = $messages[$message_code] ?? ucfirst(str_replace('_', ' ', $message_code)); // Mensagem genérica se código não mapeado

        if (isset($_GET['dia_semana_label'])) { // Para erro de intervalo de horário
            $message_text .= ' '. sprintf(__('Dia: %s', 'agendamentos-wp'), sanitize_text_field($_GET['dia_semana_label']));
        }
        if (isset($_GET['wp_error_code'])) {
             $message_text .= ' Código do erro WP: ' . sanitize_text_field($_GET['wp_error_code']);
        }

        echo "<div class='notice notice-{$type} is-dismissible'><p>{$message_text}</p></div>";
    }
}
add_action( 'admin_notices', 'agendamentos_wp_admin_notices' );

function agendamentos_wp_admin_enqueue_scripts( $hook_suffix ) { /* Conteúdo completo da função, como definido anteriormente */ }
add_action( 'admin_enqueue_scripts', 'agendamentos_wp_admin_enqueue_scripts' );

function agendamentos_wp_admin_menu() { /* Conteúdo completo da função, como definido anteriormente */ }
add_action( 'admin_menu', 'agendamentos_wp_admin_menu' );


// ########## API REST ##########
function agendamentos_wp_validate_positive_integer( $param, $request, $key ) { /* ... */ return is_numeric( $param ) && absint( $param ) > 0;}
function agendamentos_wp_validate_positive_integer_or_empty( $param, $request, $key ) { if ( empty( $param ) ) { return true; } return is_numeric( $param ) && absint( $param ) > 0; }
function agendamentos_wp_validate_date_format( $param, $request, $key ) { if (preg_match( '/^\d{4}-\d{2}-\d{2}$/', $param ) !== 1) { return new WP_Error('rest_invalid_param', __('O formato da data deve ser YYYY-MM-DD.', 'agendamentos-wp'), array('status' => 400)); } list($year, $month, $day) = explode('-', $param); if (!checkdate((int)$month, (int)$day, (int)$year)) { return new WP_Error('rest_invalid_param', __('Data inválida.', 'agendamentos-wp'), array('status' => 400)); } return true; }
function agendamentos_wp_validate_date_format_from_route( $param, $request, $key ) { list($year, $month, $day) = explode('-', $param); if (!checkdate((int)$month, (int)$day, (int)$year)) { return new WP_Error('rest_invalid_param', __('Data inválida na URL.', 'agendamentos-wp'), array('status' => 400));} return true;}
function agendamentos_wp_validate_time_format( $param, $request, $key ) { if (preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $param ) !== 1) { return new WP_Error('rest_invalid_param', __('O formato da hora deve ser HH:MM (24h).', 'agendamentos-wp'), array('status' => 400)); } return true; }
function agendamentos_wp_validate_status_agendamento( $param, $request, $key ) { $allowed_statuses = array( 'pendente', 'confirmado', 'cancelado', 'concluido' ); if ( ! in_array( $param, $allowed_statuses, true ) ) { return new WP_Error( 'rest_invalid_param', sprintf( __( 'Status inválido. Valores permitidos: %s.', 'agendamentos-wp' ), implode( ', ', $allowed_statuses ) ), array( 'status' => 400 ) ); } return true; }
function agendamentos_wp_permission_check_manage_agendamentos() { return current_user_can( 'manage_options' ); }
function agendamentos_wp_check_availability($profissional_id, $servico_id, $data_agendamento, $hora_agendamento, $agendamento_id_to_exclude = 0) { /* Conteúdo completo da função */ }
function agendamentos_wp_get_available_slots_for_day($profissional_id, $servico_id, $data_consulta) { /* Conteúdo completo da função */ }
function agendamentos_wp_api_get_disponibilidade( WP_REST_Request $request ) { /* Conteúdo completo da função */ }
function agendamentos_wp_api_create_agendamento( WP_REST_Request $request ) { /* Conteúdo completo da função */ }
function agendamentos_wp_api_get_agendamentos_by_date( WP_REST_Request $request ) { /* Conteúdo completo da função */ }
function agendamentos_wp_api_get_servicos( WP_REST_Request $request ) { /* Conteúdo completo da função */ }
function agendamentos_wp_api_create_cliente( WP_REST_Request $request ) { /* Conteúdo completo da função */ }
function agendamentos_wp_api_find_cliente_by_phone( WP_REST_Request $request ) { /* Conteúdo completo da função */ }
function agendamentos_wp_api_update_agendamento_status( WP_REST_Request $request ) { /* Conteúdo completo da função */ }
function agendamentos_wp_api_reschedule_agendamento( WP_REST_Request $request ) { /* Conteúdo completo da função */ }

function agendamentos_wp_register_api_routes() {
    $namespace = 'agendamentos/v1';
    // GET /servicos
    register_rest_route( $namespace, '/servicos', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'agendamentos_wp_api_get_servicos', 'permission_callback' => '__return_true' ) );
    // POST /clientes
    register_rest_route( $namespace, '/clientes', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'agendamentos_wp_api_create_cliente', 'permission_callback' => function() { return is_user_logged_in(); }, 'args' => array( 'nome_cliente' => array('required'=>true,'type'=>'string','sanitize_callback'=>'sanitize_text_field'), 'telefone' => array('required'=>true,'type'=>'string','sanitize_callback'=>'sanitize_text_field'), 'email' => array('required'=>false,'type'=>'string','sanitize_callback'=>'sanitize_email','validate_callback'=>'is_email') ) ) );
    // GET /clientes/buscar
    register_rest_route( $namespace, '/clientes/buscar', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'agendamentos_wp_api_find_cliente_by_phone', 'permission_callback' => '__return_true', 'args' => array('telefone'=>array('required'=>true,'type'=>'string','sanitize_callback'=>'sanitize_text_field')) ) );
    // POST /agendamentos
    register_rest_route( $namespace, '/agendamentos', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'agendamentos_wp_api_create_agendamento', 'permission_callback' => function() { return is_user_logged_in(); }, 'args' => array( 'servico_id'=>array('required'=>true,'type'=>'integer','sanitize_callback'=>'absint','validate_callback'=>'agendamentos_wp_validate_positive_integer'), 'profissional_id'=>array('required'=>true,'type'=>'integer','sanitize_callback'=>'absint','validate_callback'=>'agendamentos_wp_validate_positive_integer'), 'cliente_id'=>array('required'=>true,'type'=>'integer','sanitize_callback'=>'absint','validate_callback'=>'agendamentos_wp_validate_positive_integer'), 'data_agendamento'=>array('required'=>true,'type'=>'string','validate_callback'=>'agendamentos_wp_validate_date_format','sanitize_callback'=>'sanitize_text_field'), 'hora_agendamento'=>array('required'=>true,'type'=>'string','validate_callback'=>'agendamentos_wp_validate_time_format','sanitize_callback'=>'sanitize_text_field') ) ) );
    // GET /agendamentos/data/{data}
    register_rest_route( $namespace, '/agendamentos/data/(?P<data>\d{4}-\d{2}-\d{2})', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'agendamentos_wp_api_get_agendamentos_by_date', 'permission_callback' => '__return_true', 'args' => array('data'=>array('validate_callback'=>'agendamentos_wp_validate_date_format_from_route','sanitize_callback'=>'sanitize_text_field'))));
    // GET /disponibilidade
    register_rest_route( $namespace, '/disponibilidade', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'agendamentos_wp_api_get_disponibilidade', 'permission_callback' => '__return_true', 'args' => array( 'profissional_id'=>array('required'=>true,'type'=>'integer','sanitize_callback'=>'absint','validate_callback'=>'agendamentos_wp_validate_positive_integer'), 'servico_id'=>array('required'=>true,'type'=>'integer','sanitize_callback'=>'absint','validate_callback'=>'agendamentos_wp_validate_positive_integer'), 'data'=>array('required'=>true,'type'=>'string','validate_callback'=>'agendamentos_wp_validate_date_format','sanitize_callback'=>'sanitize_text_field') ) ) );
    // PUT /agendamentos/{id} (Atualizar status)
    register_rest_route( $namespace, '/agendamentos/(?P<id>\d+)', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => 'agendamentos_wp_api_update_agendamento_status', 'permission_callback' => 'agendamentos_wp_permission_check_manage_agendamentos', 'args' => array( 'id'=>array('validate_callback'=>'agendamentos_wp_validate_positive_integer'), 'status'=>array('required'=>true,'type'=>'string','sanitize_callback'=>'sanitize_text_field','validate_callback'=>'agendamentos_wp_validate_status_agendamento') ) ) );
    // PUT /agendamentos/reagendar/{id}
    register_rest_route( $namespace, '/agendamentos/reagendar/(?P<id>\d+)', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => 'agendamentos_wp_api_reschedule_agendamento', 'permission_callback' => 'agendamentos_wp_permission_check_manage_agendamentos', 'args' => array( 'id'=>array('validate_callback'=>'agendamentos_wp_validate_positive_integer'), 'nova_data'=>array('required'=>true,'type'=>'string','validate_callback'=>'agendamentos_wp_validate_date_format','sanitize_callback'=>'sanitize_text_field'), 'nova_hora'=>array('required'=>true,'type'=>'string','validate_callback'=>'agendamentos_wp_validate_time_format','sanitize_callback'=>'sanitize_text_field'), 'novo_profissional_id'=>array('required'=>false,'type'=>'integer','sanitize_callback'=>'absint','validate_callback'=>'agendamentos_wp_validate_positive_integer_or_empty') ) ) );
}
add_action( 'rest_api_init', 'agendamentos_wp_register_api_routes' );

?>
