<?php
/**
 * Plugin Name:       Agendamentos WP
 * Plugin URI:        https://example.com/agendamentos-wp
 * Description:       Plugin para gerenciamento de agendamentos, profissionais, serviços e clientes.
 * Version:           0.1.0
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

/**
 * Função chamada quando o plugin é ativado.
 */
function agendamentos_wp_ativar() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $charset_collate = $wpdb->get_charset_collate();

    // Tabela Profissionais
    $table_name_profissionais = $wpdb->prefix . 'profissionais';
    $sql_profissionais = "CREATE TABLE $table_name_profissionais (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        nome_profissional VARCHAR(255) NOT NULL,
        telefone VARCHAR(20),
        nome_usuario VARCHAR(60) NOT NULL,
        senha VARCHAR(255) NOT NULL,
        tipo_comissao VARCHAR(20) COMMENT 'valor_fixo, porcentagem',
        valor_fixo DECIMAL(10,2),
        porcentagem INT(3),
        user_id BIGINT(20) UNSIGNED,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_profissionais );

    // Tabela Horarios
    $table_name_horarios = $wpdb->prefix . 'horarios';
    $sql_horarios = "CREATE TABLE $table_name_horarios (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        profissional_id BIGINT(20) UNSIGNED NOT NULL,
        dia_semana INT(1) NOT NULL COMMENT '0 para Domingo, 1 para Segunda, ..., 6 para Sábado',
        horario_inicio TIME,
        horario_termino TIME,
        inicio_almoco TIME,
        termino_almoco TIME,
        pausa_entre_atendimentos INT(11) COMMENT 'Em minutos',
        dia_folga BOOLEAN DEFAULT 0,
        PRIMARY KEY  (id),
        FOREIGN KEY (profissional_id) REFERENCES $table_name_profissionais(id) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta( $sql_horarios );

    // Tabela Servicos
    $table_name_servicos = $wpdb->prefix . 'servicos';
    $sql_servicos = "CREATE TABLE $table_name_servicos (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        nome_servico VARCHAR(255) NOT NULL,
        descricao TEXT,
        duracao INT(11) NOT NULL COMMENT 'Em minutos',
        preco DECIMAL(10,2) NOT NULL,
        exigir_sinal BOOLEAN DEFAULT 0,
        porcentagem_sinal INT(3),
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_servicos );

    // Tabela Servicos_Profissionais
    $table_name_servicos_profissionais = $wpdb->prefix . 'servicos_profissionais';
    $sql_servicos_profissionais = "CREATE TABLE $table_name_servicos_profissionais (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        servico_id BIGINT(20) UNSIGNED NOT NULL,
        profissional_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY servico_profissional (servico_id, profissional_id),
        FOREIGN KEY (servico_id) REFERENCES $table_name_servicos(id) ON DELETE CASCADE,
        FOREIGN KEY (profissional_id) REFERENCES $table_name_profissionais(id) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta( $sql_servicos_profissionais );

    // Tabela Clientes
    $table_name_clientes = $wpdb->prefix . 'clientes';
    $sql_clientes = "CREATE TABLE $table_name_clientes (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        nome_cliente VARCHAR(255) NOT NULL,
        telefone VARCHAR(20) NOT NULL UNIQUE,
        valor_gasto DECIMAL(10,2) DEFAULT 0.00,
        user_id BIGINT(20) UNSIGNED,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_clientes );

    // Tabela Agendamentos
    $table_name_agendamentos = $wpdb->prefix . 'agendamentos';
    $sql_agendamentos = "CREATE TABLE $table_name_agendamentos (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        servico_id BIGINT(20) UNSIGNED NOT NULL,
        profissional_id BIGINT(20) UNSIGNED NOT NULL,
        cliente_id BIGINT(20) UNSIGNED NOT NULL,
        data_agendamento DATE NOT NULL,
        hora_agendamento TIME NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pendente' COMMENT 'pendente, confirmado, cancelado, concluido',
        PRIMARY KEY  (id),
        FOREIGN KEY (servico_id) REFERENCES $table_name_servicos(id) ON DELETE CASCADE,
        FOREIGN KEY (profissional_id) REFERENCES $table_name_profissionais(id) ON DELETE CASCADE,
        FOREIGN KEY (cliente_id) REFERENCES $table_name_clientes(id) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta( $sql_agendamentos );
}

/**
 * Adiciona roles personalizadas na ativação do plugin.
 */
function agendamentos_wp_add_roles_on_activation() {
    add_role(
        'profissionais',
        __( 'Profissional', 'agendamentos-wp' ),
        array(
            'read' => true,
            // Adicione outras capacidades aqui conforme necessário
            // Ex: 'edit_posts' => false, 'delete_posts' => false,
        )
    );
     add_role(
        'cliente',
        __( 'Cliente', 'agendamentos-wp' ),
        array(
            'read' => true,
        )
    );
}

/**
 * Função principal de ativação do plugin.
 * Chama as funções para criar tabelas e adicionar roles.
 */
function agendamentos_wp_plugin_activate() {
    agendamentos_wp_ativar();
    agendamentos_wp_add_roles_on_activation();
}
register_activation_hook( __FILE__, 'agendamentos_wp_plugin_activate' );

/**
 * Função chamada quando o plugin é desativado.
 */
function agendamentos_wp_desativar() {
    // Lógica para limpar dados, se necessário.
    // Considere remover a role 'profissionais' se o plugin for desinstalado.
}
register_deactivation_hook( __FILE__, 'agendamentos_wp_desativar' );

/**
 * Renderiza a página principal do menu Agendamentos.
 */
function agendamentos_wp_main_page_render() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Olá Admin - Agendamentos WP', 'agendamentos-wp' ); ?></h1>
        <p><?php esc_html_e( 'Bem-vindo à página principal do plugin Agendamentos WP.', 'agendamentos-wp' ); ?></p>
    </div>
    <?php
}

/**
 * Renderiza a página para adicionar um novo profissional.
 */
function agendamentos_wp_add_profissional_page_render() {
    $profissional_id = isset( $_GET['profissional_id'] ) ? intval( $_GET['profissional_id'] ) : 0;
    $action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'add';
    $is_edit_mode = ( $action === 'edit' && $profissional_id > 0 );
    $form_data = array(
        'nome_profissional' => '',
        'telefone' => '',
        'nome_usuario_wp' => '',
        'adicionar_comissao' => false,
        'tipo_comissao' => 'valor_fixo',
        'valor_fixo_comissao' => '',
        'porcentagem_comissao' => '',
    );
    $page_title = __( 'Adicionar Novo Profissional', 'agendamentos-wp' );
    $submit_button_text = __( 'Salvar Profissional', 'agendamentos-wp' );

    if ( $is_edit_mode ) {
        // Verificar nonce para edição
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'agendamentos_wp_edit_profissional_nonce_' . $profissional_id ) ) {
            wp_die( __( 'Falha na verificação de segurança (nonce) para edição.', 'agendamentos-wp' ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'profissionais';
        $profissional_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $profissional_id ) );

        if ( $profissional_data ) {
            $page_title = __( 'Editar Profissional', 'agendamentos-wp' );
            $submit_button_text = __( 'Atualizar Profissional', 'agendamentos-wp' );
            $form_data['nome_profissional'] = $profissional_data->nome_profissional;
            $form_data['telefone'] = $profissional_data->telefone;
            $form_data['nome_usuario_wp'] = $profissional_data->nome_usuario; // nome_usuario da tabela profissionais é o nome_usuario_wp
            // A senha WP não é pré-preenchida por segurança. O usuário WP pode alterá-la separadamente.
            // Ou podemos adicionar campos para alterar a senha WP aqui, se necessário.

            if ( !is_null($profissional_data->tipo_comissao) && $profissional_data->tipo_comissao !== '' ) {
                $form_data['adicionar_comissao'] = true;
                $form_data['tipo_comissao'] = $profissional_data->tipo_comissao;
                if ( $profissional_data->tipo_comissao === 'valor_fixo' ) {
                    $form_data['valor_fixo_comissao'] = $profissional_data->valor_fixo;
                } elseif ( $profissional_data->tipo_comissao === 'porcentagem' ) {
                    $form_data['porcentagem_comissao'] = $profissional_data->porcentagem;
                }
            }
        } else {
            // Profissional não encontrado, voltar para modo de adição ou mostrar erro
            $is_edit_mode = false;
            // Poderia adicionar uma admin notice aqui
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( $page_title ); ?></h1>
        <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="agendamentos_wp_save_profissional">
            <?php wp_nonce_field( 'agendamentos_wp_save_profissional_action', 'agendamentos_wp_save_profissional_nonce' ); ?>
            <?php if ( $is_edit_mode && isset($profissional_id) ) : ?>
                <input type="hidden" name="profissional_id" value="<?php echo esc_attr( $profissional_id ); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="nome_profissional"><?php esc_html_e( 'Nome do Profissional', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="nome_profissional" name="nome_profissional" class="regular-text" value="<?php echo esc_attr( $form_data['nome_profissional'] ); ?>" required />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="telefone"><?php esc_html_e( 'Telefone', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="tel" id="telefone" name="telefone" class="regular-text" value="<?php echo esc_attr( $form_data['telefone'] ); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="nome_usuario_wp"><?php esc_html_e( 'Nome de Usuário (WP)', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="nome_usuario_wp" name="nome_usuario_wp" class="regular-text" value="<?php echo esc_attr( $form_data['nome_usuario_wp'] ); ?>" <?php echo $is_edit_mode ? 'disabled' : 'required'; ?> />
                        <p class="description">
                            <?php
                            if ($is_edit_mode) {
                                esc_html_e( 'O nome de usuário WP não pode ser alterado diretamente aqui. Para alterar o nome de usuário WP, edite o perfil do usuário correspondente.', 'agendamentos-wp' );
                            } else {
                                esc_html_e( 'Este será o nome de usuário para login no WordPress.', 'agendamentos-wp' );
                            }
                            ?>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="senha_wp"><?php esc_html_e( 'Senha (WP)', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="password" id="senha_wp" name="senha_wp" class="regular-text" <?php echo $is_edit_mode ? '' : 'required'; ?> />
                        <?php if ($is_edit_mode) : ?>
                            <p class="description"><?php esc_html_e( 'Deixe em branco para não alterar a senha WP existente.', 'agendamentos-wp' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Comissão', 'agendamentos-wp' ); ?></th>
                    <td>
                        <label for="toggle_comissao">
                            <input type="checkbox" id="toggle_comissao" name="adicionar_comissao" value="1" <?php checked( $form_data['adicionar_comissao'] ); ?> />
                            <?php esc_html_e( 'Adicionar/Modificar Comissão?', 'agendamentos-wp' ); ?>
                        </label>
                    </td>
                </tr>
                <tr valign="top" class="comissao-fields" style="<?php echo $form_data['adicionar_comissao'] ? '' : 'display: none;'; ?>">
                    <th scope="row">
                        <label for="tipo_comissao"><?php esc_html_e( 'Tipo de Comissão', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <select id="tipo_comissao" name="tipo_comissao">
                            <option value="valor_fixo" <?php selected( $form_data['tipo_comissao'], 'valor_fixo' ); ?>><?php esc_html_e( 'Valor Fixo', 'agendamentos-wp' ); ?></option>
                            <option value="porcentagem" <?php selected( $form_data['tipo_comissao'], 'porcentagem' ); ?>><?php esc_html_e( 'Porcentagem', 'agendamentos-wp' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top" class="comissao-valor-fixo-field comissao-fields" style="<?php echo ($form_data['adicionar_comissao'] && $form_data['tipo_comissao'] === 'valor_fixo') ? '' : 'display: none;'; ?>">
                    <th scope="row">
                        <label for="valor_fixo_comissao"><?php esc_html_e( 'Valor Fixo (R$)', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="number" step="0.01" id="valor_fixo_comissao" name="valor_fixo_comissao" class="regular-text" value="<?php echo esc_attr( $form_data['valor_fixo_comissao'] ); ?>" />
                    </td>
                </tr>
                <tr valign="top" class="comissao-porcentagem-field comissao-fields" style="<?php echo ($form_data['adicionar_comissao'] && $form_data['tipo_comissao'] === 'porcentagem') ? '' : 'display: none;'; ?>">
                    <th scope="row">
                        <label for="porcentagem_comissao"><?php esc_html_e( 'Porcentagem (%)', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="number" step="1" id="porcentagem_comissao" name="porcentagem_comissao" class="regular-text" value="<?php echo esc_attr( $form_data['porcentagem_comissao'] ); ?>" />
                    </td>
                </tr>
            </table>

            <?php submit_button( $submit_button_text ); ?>
        </form>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleComissaoFields() {
                if ($('#toggle_comissao').is(':checked')) {
                    $('.comissao-fields').show();
                    var tipoComissao = $('#tipo_comissao').val();
                    if (tipoComissao === 'valor_fixo') {
                        $('.comissao-valor-fixo-field').show();
                        $('.comissao-porcentagem-field').hide();
                    } else if (tipoComissao === 'porcentagem') {
                        $('.comissao-valor-fixo-field').hide();
                        $('.comissao-porcentagem-field').show();
                    }
                } else {
                    $('.comissao-fields').hide();
                    $('.comissao-valor-fixo-field').hide();
                    $('.comissao-porcentagem-field').hide();
                }
            }

            $('#toggle_comissao').on('change', function() {
                toggleComissaoFields();
            });

            $('#tipo_comissao').on('change', function() {
                toggleComissaoFields();
            });

            // Initial check
            toggleComissaoFields();
        });
    </script>
    <?php
}

/**
 * Cria um usuário WordPress para um cliente.
 *
 * @param string $nome_cliente Nome do cliente.
 * @param string $telefone_cliente Telefone do cliente (usado para gerar username).
 * @param string $email_cliente Email do cliente (opcional).
 * @return WP_User|WP_Error WP_User em sucesso, WP_Error em falha.
 */
function agendamentos_wp_create_cliente_user( $nome_cliente, $telefone_cliente, $email_cliente = '' ) {
    // Gerar um nome de usuário único. Ex: cliente_hashtelefone
    // Remover não-numéricos do telefone para o hash, para consistência
    $telefone_numerico = preg_replace( '/\D/', '', $telefone_cliente );
    $username_base = 'cliente_' . substr( md5( $telefone_numerico ), 0, 8 ); // Pega os primeiros 8 chars do hash
    $username = $username_base;
    $counter = 1;
    while ( username_exists( $username ) ) {
        $username = $username_base . '_' . $counter;
        $counter++;
    }

    $password = wp_generate_password( 12, true, true );

    $user_data = array(
        'user_login' => $username,
        'user_pass'  => $password,
        'display_name' => $nome_cliente,
        'role'       => 'cliente',
    );

    if ( ! empty( $email_cliente ) && is_email( $email_cliente ) ) {
        if ( email_exists( $email_cliente ) ) {
            return new WP_Error( 'email_exists', __( 'Este email já está registrado para outro usuário.', 'agendamentos-wp' ) );
        }
        $user_data['user_email'] = $email_cliente;
    } else {
        // Se o email não for fornecido ou for inválido, podemos gerar um placeholder se necessário,
        // ou omitir, dependendo da configuração do WordPress.
        // Por segurança, é melhor ter um email, mesmo que placeholder, para evitar problemas com wp_create_user.
        // No entanto, se o email não é obrigatório para a role cliente e não queremos emails falsos,
        // poderíamos tentar criar sem. wp_create_user lida com email vazio.
    }

    $user_id = wp_create_user( $user_data['user_login'], $user_data['user_pass'], isset($user_data['user_email']) ? $user_data['user_email'] : '' );

    if ( is_wp_error( $user_id ) ) {
        return $user_id; // Retorna o objeto WP_Error
    }

    // Definir a role explicitamente após a criação, caso o argumento 'role' em wp_create_user não funcione como esperado em todas as versões/configurações.
    $wp_user = new WP_User( $user_id );
    $wp_user->set_role( 'cliente' );

    // Você pode querer enviar uma notificação para o cliente com seus dados de login
    // wp_new_user_notification( $user_id, null, 'both' ); // Cuidado com emails placeholder

    return $wp_user;
}


/**
 * Lida com o salvamento dos dados do formulário de adicionar cliente.
 */
function agendamentos_wp_handle_save_cliente() {
    // 1. Validação e Segurança
    if ( ! isset( $_POST['agendamentos_wp_save_cliente_nonce'] ) || ! wp_verify_nonce( $_POST['agendamentos_wp_save_cliente_nonce'], 'agendamentos_wp_save_cliente_action' ) ) {
        wp_die( __( 'Falha na verificação de segurança (nonce).', 'agendamentos-wp' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) { // Ou uma capacidade mais específica
        wp_die( __( 'Você não tem permissão para executar esta ação.', 'agendamentos-wp' ) );
    }

    $redirect_url = admin_url( 'admin.php?page=agendamentos-wp-clientes' );

    // 2. Sanitizar e validar dados do POST
    $nome_cliente = isset( $_POST['nome_cliente'] ) ? sanitize_text_field( $_POST['nome_cliente'] ) : '';
    $telefone_cliente = isset( $_POST['telefone_cliente'] ) ? sanitize_text_field( $_POST['telefone_cliente'] ) : '';
    $email_cliente = isset( $_POST['email_cliente'] ) ? sanitize_email( $_POST['email_cliente'] ) : '';


    // Validações básicas
    if ( empty( $nome_cliente ) || empty( $telefone_cliente ) ) {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Erro: Nome do cliente e telefone são obrigatórios.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }

    if ( !empty($email_cliente) && !is_email($email_cliente) ) {
         $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Erro: O email fornecido não é válido.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }

    global $wpdb;
    $table_name_clientes = $wpdb->prefix . 'clientes';

    // Verificar telefone duplicado na tabela clientes
    $telefone_existente = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name_clientes WHERE telefone = %s", $telefone_cliente ) );
    if ( $telefone_existente ) {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Erro: Este telefone já está cadastrado para outro cliente.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }

    // 3. Criar usuário WordPress para o cliente
    $user_object = agendamentos_wp_create_cliente_user( $nome_cliente, $telefone_cliente, $email_cliente );

    if ( is_wp_error( $user_object ) ) {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Erro ao criar conta de usuário para o cliente: ', 'agendamentos-wp' ) . $user_object->get_error_message(),
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }
    $user_id_wp = $user_object->ID;

    // 4. Salvar Dados na Tabela `clientes`
    $data_cliente = array(
        'nome_cliente' => $nome_cliente,
        'telefone'     => $telefone_cliente,
        'user_id'      => $user_id_wp,
        'valor_gasto'  => 0.00, // Valor inicial
    );
    $format_cliente = array( '%s', '%s', '%d', '%f' );

    $result = $wpdb->insert( $table_name_clientes, $data_cliente, $format_cliente );

    if ( $result === false ) {
        // Tentar limpar o usuário WP criado se a inserção na tabela personalizada falhar.
        wp_delete_user( $user_id_wp );
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Erro ao salvar dados do cliente no banco de dados: ', 'agendamentos-wp' ) . $wpdb->last_error,
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }

    // 5. Redirecionamento e Feedback
    $redirect_url = add_query_arg( array(
        'agendamentos_wp_message' => __( 'Cliente adicionado com sucesso! Um usuário WP foi criado.', 'agendamentos-wp' ),
        'agendamentos_wp_message_type' => 'success',
    ), $redirect_url );
    wp_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_agendamentos_wp_save_cliente', 'agendamentos_wp_handle_save_cliente' );


/**
 * Adiciona os itens de menu no painel administrativo do WordPress.
 */
function agendamentos_wp_admin_menu() {
    add_menu_page(
        __( 'Agendamentos', 'agendamentos-wp' ),
        __( 'Agendamentos', 'agendamentos-wp' ),
        'manage_options',
        'agendamentos-wp-main',
        'agendamentos_wp_main_page_render',
        'dashicons-calendar-alt',
        26
    );

    add_submenu_page(
        'agendamentos-wp-main',
        __( 'Adicionar Novo Profissional', 'agendamentos-wp' ),
        __( 'Adicionar Profissional', 'agendamentos-wp' ),
        'manage_options',
        'agendamentos-wp-add-profissional',
        'agendamentos_wp_add_profissional_page_render'
    );

    add_submenu_page(
        'agendamentos-wp-main',
        __( 'Todos os Profissionais', 'agendamentos-wp' ),
        __( 'Todos os Profissionais', 'agendamentos-wp' ),
        'manage_options',
        'agendamentos-wp-all-profissionais',
        'agendamentos_wp_all_profissionais_page_render'
    );
}
add_action( 'admin_menu', 'agendamentos_wp_admin_menu' );

/**
 * Renderiza a página "Todos os Profissionais".
 */
function agendamentos_wp_all_profissionais_page_render() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Todos os Profissionais', 'agendamentos-wp' ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=agendamentos-wp-add-profissional' ) ); ?>" class="page-title-action">
                <?php esc_html_e( 'Adicionar Novo', 'agendamentos-wp' ); ?>
            </a>
        </h1>

        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'profissionais';
        $profissionais = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY nome_profissional ASC" );
        ?>

        <div class="profissionais-list-table-wrapper">
            <table class="wp-list-table widefat fixed striped table-view-list posts">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e( 'Nome do Profissional', 'agendamentos-wp' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Telefone', 'agendamentos-wp' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Tipo Comissão', 'agendamentos-wp' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Valor Comissão', 'agendamentos-wp' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Ações', 'agendamentos-wp' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php if ( $profissionais ) : ?>
                        <?php foreach ( $profissionais as $profissional ) : ?>
                            <?php
                            $edit_nonce = wp_create_nonce( 'agendamentos_wp_edit_profissional_nonce_' . $profissional->id );
                            $delete_nonce = wp_create_nonce( 'agendamentos_wp_delete_profissional_nonce_' . $profissional->id );
                            $edit_link = admin_url( 'admin.php?page=agendamentos-wp-add-profissional&action=edit&profissional_id=' . $profissional->id . '&_wpnonce=' . $edit_nonce );
                            // A ação de exclusão apontará para admin-post.php
                            $delete_link_base = admin_url( 'admin-post.php' );
														$delete_link = add_query_arg( array(
																'action' => 'agendamentos_wp_delete_profissional',
																'profissional_id' => $profissional->id,
																'_wpnonce' => $delete_nonce
														), $delete_link_base );

                            $tipo_comissao_display = __( 'N/A', 'agendamentos-wp' );
                            $valor_comissao_display = __( 'N/A', 'agendamentos-wp' );

                            if ( $profissional->tipo_comissao ) {
                                if ( $profissional->tipo_comissao === 'valor_fixo' ) {
                                    $tipo_comissao_display = __( 'Valor Fixo', 'agendamentos-wp' );
                                    $valor_comissao_display = 'R$ ' . number_format_i18n( $profissional->valor_fixo, 2 );
                                } elseif ( $profissional->tipo_comissao === 'porcentagem' ) {
                                    $tipo_comissao_display = __( 'Porcentagem', 'agendamentos-wp' );
                                    $valor_comissao_display = $profissional->porcentagem . '%';
                                }
                            }
                            ?>
                            <tr>
                                <td class="column-primary">
                                    <strong><?php echo esc_html( $profissional->nome_profissional ); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit"><a href="<?php echo esc_url( $edit_link ); ?>"><?php esc_html_e( 'Editar', 'agendamentos-wp' ); ?></a> | </span>
                                        <span class="delete"><a href="<?php echo esc_url( $delete_link ); ?>" onclick="return confirm('<?php esc_attr_e( 'Tem certeza que deseja excluir este profissional e o usuário WP associado?', 'agendamentos-wp' ); ?>');"><?php esc_html_e( 'Excluir', 'agendamentos-wp' ); ?></a></span>
                                    </div>
                                </td>
                                <td><?php echo esc_html( $profissional->telefone ); ?></td>
                                <td><?php echo esc_html( $tipo_comissao_display ); ?></td>
                                <td><?php echo esc_html( $valor_comissao_display ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( $edit_link ); ?>" class="button button-small"><?php esc_html_e( 'Editar', 'agendamentos-wp' ); ?></a>
                                    <a href="<?php echo esc_url( $delete_link ); ?>" class="button button-small delete-button" onclick="return confirm('<?php esc_attr_e( 'Tem certeza que deseja excluir este profissional e o usuário WP associado?', 'agendamentos-wp' ); ?>');"><?php esc_html_e( 'Excluir', 'agendamentos-wp' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'Nenhum profissional encontrado.', 'agendamentos-wp' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Aqui virá a lista para mobile -->
        <div class="profissionais-list-mobile" style="display:none;">
            <ul>
            <?php if ( $profissionais ) : ?>
                <?php foreach ( $profissionais as $profissional ) : ?>
                    <?php
                        // Recalcular nonces e links para garantir que estão no escopo correto do loop
                        $edit_nonce_mobile = wp_create_nonce( 'agendamentos_wp_edit_profissional_nonce_mobile_' . $profissional->id );
                        $delete_nonce_mobile = wp_create_nonce( 'agendamentos_wp_delete_profissional_nonce_mobile_' . $profissional->id );
                        $edit_link_mobile = admin_url( 'admin.php?page=agendamentos-wp-add-profissional&action=edit&profissional_id=' . $profissional->id . '&_wpnonce=' . $edit_nonce_mobile );
                        $delete_link_base_mobile = admin_url( 'admin-post.php' );
												$delete_link_mobile = add_query_arg( array(
														'action' => 'agendamentos_wp_delete_profissional',
														'profissional_id' => $profissional->id,
														'_wpnonce' => $delete_nonce_mobile
												), $delete_link_base_mobile );

                        $tipo_comissao_display_mobile = __( 'N/A', 'agendamentos-wp' );
                        $valor_comissao_display_mobile = __( 'N/A', 'agendamentos-wp' );
                        if ( $profissional->tipo_comissao ) {
                            if ( $profissional->tipo_comissao === 'valor_fixo' ) {
                                $tipo_comissao_display_mobile = __( 'Valor Fixo', 'agendamentos-wp' );
                                $valor_comissao_display_mobile = 'R$ ' . number_format_i18n( $profissional->valor_fixo, 2 );
                            } elseif ( $profissional->tipo_comissao === 'porcentagem' ) {
                                $tipo_comissao_display_mobile = __( 'Porcentagem', 'agendamentos-wp' );
                                $valor_comissao_display_mobile = $profissional->porcentagem . '%';
                            }
                        }
                    ?>
                    <li>
                        <strong><?php esc_html_e( 'Nome:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $profissional->nome_profissional ); ?><br>
                        <strong><?php esc_html_e( 'Telefone:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $profissional->telefone ); ?><br>
                        <strong><?php esc_html_e( 'Comissão:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $tipo_comissao_display_mobile ); ?> (<?php echo esc_html( $valor_comissao_display_mobile ); ?>)<br>
                        <a href="<?php echo esc_url( $edit_link_mobile ); ?>"><?php esc_html_e( 'Editar', 'agendamentos-wp' ); ?></a> |
                        <a href="<?php echo esc_url( $delete_link_mobile ); ?>" onclick="return confirm('<?php esc_attr_e( 'Tem certeza que deseja excluir este profissional e o usuário WP associado?', 'agendamentos-wp' ); ?>');"><?php esc_html_e( 'Excluir', 'agendamentos-wp' ); ?></a>
                    </li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php esc_html_e( 'Nenhum profissional encontrado.', 'agendamentos-wp' ); ?></li>
            <?php endif; ?>
            </ul>
        </div>

    </div>
    <?php
}

/**
 * Exibe notices no painel administrativo.
 */
function agendamentos_wp_admin_notices() {
    if ( isset( $_GET['agendamentos_wp_message'] ) ) {
        $message = sanitize_text_field( $_GET['agendamentos_wp_message'] );
        $type = isset( $_GET['agendamentos_wp_message_type'] ) ? sanitize_text_field( $_GET['agendamentos_wp_message_type'] ) : 'info'; // success, error, warning, info
        if ( $message ) {
            echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
        }
    }
}
add_action( 'admin_notices', 'agendamentos_wp_admin_notices' );

/**
 * Lida com o salvamento dos dados do formulário de adicionar profissional.
 */
function agendamentos_wp_handle_save_profissional() {
    // 1. Validação e Segurança
    if ( ! isset( $_POST['agendamentos_wp_save_profissional_nonce'] ) || ! wp_verify_nonce( $_POST['agendamentos_wp_save_profissional_nonce'], 'agendamentos_wp_save_profissional_action' ) ) {
        wp_die( __( 'Falha na verificação de segurança (nonce).', 'agendamentos-wp' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Você não tem permissão para executar esta ação.', 'agendamentos-wp' ) );
    }

    $profissional_id_update = isset( $_POST['profissional_id'] ) ? intval( $_POST['profissional_id'] ) : 0;
    $is_update = $profissional_id_update > 0;

    $redirect_url = admin_url( 'admin.php?page=agendamentos-wp-all-profissionais' );
    if ( !$is_update ) { // Se for criação, pode redirecionar para a página de adicionar.
        $redirect_url = admin_url( 'admin.php?page=agendamentos-wp-add-profissional' );
    }


    // 2. Sanitizar e validar dados do POST
    $nome_profissional = isset( $_POST['nome_profissional'] ) ? sanitize_text_field( $_POST['nome_profissional'] ) : '';
    $telefone = isset( $_POST['telefone'] ) ? sanitize_text_field( $_POST['telefone'] ) : '';
    // nome_usuario_wp só é pego se não for modo de edição, pois o campo estará desabilitado
    $nome_usuario_wp = '';
    if (!$is_update) {
        $nome_usuario_wp = isset( $_POST['nome_usuario_wp'] ) ? sanitize_user( $_POST['nome_usuario_wp'] ) : '';
    }
    $senha_wp = isset( $_POST['senha_wp'] ) ? $_POST['senha_wp'] : ''; // Não sanitizar senha aqui, wp_create_user/wp_update_user cuidam disso
    $adicionar_comissao = isset( $_POST['adicionar_comissao'] ) ? true : false;
    $tipo_comissao = isset( $_POST['tipo_comissao'] ) ? sanitize_text_field( $_POST['tipo_comissao'] ) : null;
    $valor_fixo_comissao = isset( $_POST['valor_fixo_comissao'] ) ? floatval( $_POST['valor_fixo_comissao'] ) : null;
    $porcentagem_comissao = isset( $_POST['porcentagem_comissao'] ) ? intval( $_POST['porcentagem_comissao'] ) : null;

    // Validações básicas
    if ( empty( $nome_profissional ) ) {
        $message = __( 'Erro: Nome do profissional é obrigatório.', 'agendamentos-wp' );
        if ( !$is_update && (empty( $nome_usuario_wp ) || empty( $senha_wp )) ) {
             $message = __( 'Erro: Nome do profissional, nome de usuário WP e senha WP são obrigatórios.', 'agendamentos-wp' );
        } elseif ( !$is_update && empty( $nome_usuario_wp ) ) {
            $message = __( 'Erro: Nome de usuário WP é obrigatório.', 'agendamentos-wp' );
        } elseif ( !$is_update && empty( $senha_wp ) ) {
            $message = __( 'Erro: Senha WP é obrigatória.', 'agendamentos-wp' );
        }

        $error_redirect_url = $is_update ? admin_url( 'admin.php?page=agendamentos-wp-add-profissional&action=edit&profissional_id=' . $profissional_id_update ) : admin_url( 'admin.php?page=agendamentos-wp-add-profissional' );
        $error_redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => $message,
            'agendamentos_wp_message_type' => 'error',
        ), $error_redirect_url );
        wp_redirect( $error_redirect_url );
        exit;
    }


    if ( $adicionar_comissao ) {
        if ( $tipo_comissao === 'valor_fixo' && ( is_null( $valor_fixo_comissao ) || $valor_fixo_comissao < 0 ) ) {
            $error_redirect_url = $is_update ? admin_url( 'admin.php?page=agendamentos-wp-add-profissional&action=edit&profissional_id=' . $profissional_id_update ) : admin_url( 'admin.php?page=agendamentos-wp-add-profissional' );
            $error_redirect_url = add_query_arg( array(
                'agendamentos_wp_message' => __( 'Erro: Valor fixo da comissão inválido.', 'agendamentos-wp' ),
                'agendamentos_wp_message_type' => 'error',
            ), $error_redirect_url );
            wp_redirect( $error_redirect_url );
            exit;
        }
        if ( $tipo_comissao === 'porcentagem' && ( is_null( $porcentagem_comissao ) || $porcentagem_comissao < 0 || $porcentagem_comissao > 100 ) ) {
            $error_redirect_url = $is_update ? admin_url( 'admin.php?page=agendamentos-wp-add-profissional&action=edit&profissional_id=' . $profissional_id_update ) : admin_url( 'admin.php?page=agendamentos-wp-add-profissional' );
            $error_redirect_url = add_query_arg( array(
                'agendamentos_wp_message' => __( 'Erro: Porcentagem da comissão inválida.', 'agendamentos-wp' ),
                'agendamentos_wp_message_type' => 'error',
            ), $error_redirect_url );
            wp_redirect( $error_redirect_url );
            exit;
        }
    } else {
        $tipo_comissao = null;
        $valor_fixo_comissao = null;
        $porcentagem_comissao = null;
    }

    global $wpdb;
    $table_name_profissionais = $wpdb->prefix . 'profissionais';

    if ( $is_update ) {
        // ATUALIZAR PROFISSIONAL EXISTENTE
        $profissional_existente = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name_profissionais WHERE id = %d", $profissional_id_update ) );
        if ( !$profissional_existente ) {
            wp_die( __( 'Profissional não encontrado para atualização.', 'agendamentos-wp' ) );
        }

        $user_id_wp = $profissional_existente->user_id;

        // Atualizar senha do usuário WP se fornecida
        if ( !empty( $senha_wp ) ) {
            $update_user_result = wp_update_user( array( 'ID' => $user_id_wp, 'user_pass' => $senha_wp ) );
            if ( is_wp_error( $update_user_result ) ) {
                $error_redirect_url = admin_url( 'admin.php?page=agendamentos-wp-add-profissional&action=edit&profissional_id=' . $profissional_id_update );
                $error_redirect_url = add_query_arg( array(
                    'agendamentos_wp_message' => __( 'Erro ao atualizar senha do usuário WP: ', 'agendamentos-wp' ) . $update_user_result->get_error_message(),
                    'agendamentos_wp_message_type' => 'error',
                ), $error_redirect_url );
                wp_redirect( $error_redirect_url );
                exit;
            }
        }

        // Atualizar dados na tabela profissionais
        $data_update = array(
            'nome_profissional' => $nome_profissional,
            'telefone' => $telefone,
            // nome_usuario não é alterado aqui, pois é o login WP.
        );
        $format_update = array( '%s', '%s' );

        if ( $adicionar_comissao ) {
            $data_update['tipo_comissao'] = $tipo_comissao;
            $format_update[] = '%s';
            if ( $tipo_comissao === 'valor_fixo' ) {
                $data_update['valor_fixo'] = $valor_fixo_comissao;
                $data_update['porcentagem'] = null; // Garantir que o outro tipo de comissão seja nulo
                $format_update[] = '%f';
                $format_update[] = null;
            } elseif ( $tipo_comissao === 'porcentagem' ) {
                $data_update['porcentagem'] = $porcentagem_comissao;
                $data_update['valor_fixo'] = null; // Garantir que o outro tipo de comissão seja nulo
                $format_update[] = '%d';
                $format_update[] = null;
            }
        } else {
            $data_update['tipo_comissao'] = null;
            $data_update['valor_fixo'] = null;
            $data_update['porcentagem'] = null;
            $format_update[] = null;
            $format_update[] = null;
            $format_update[] = null;
        }

        // Filtra formatos nulos que podem ocorrer se a comissão for removida
        // e garante que a ordem dos formatos corresponda aos dados.
        $final_format_update = [];
        $final_data_update = [];

        if ($adicionar_comissao) {
            $final_data_update = $data_update;
            $final_format_update = ['%s', '%s', '%s']; // nome, telefone, tipo_comissao
            if ($data_update['tipo_comissao'] === 'valor_fixo') {
                $final_format_update[] = '%f'; // valor_fixo
            } else {
                 $final_data_update['valor_fixo'] = null; // Adiciona para manter a ordem na query
                 $final_format_update[] = null;
            }
            if ($data_update['tipo_comissao'] === 'porcentagem') {
                $final_format_update[] = '%d'; // porcentagem
            } else {
                $final_data_update['porcentagem'] = null; // Adiciona para manter a ordem na query
                $final_format_update[] = null;
            }
        } else {
            // Se não adicionar comissão, apenas nome e telefone são atualizados
            // E os campos de comissão são explicitamente setados para NULL
            $final_data_update = [
                'nome_profissional' => $nome_profissional,
                'telefone' => $telefone,
                'tipo_comissao' => null,
                'valor_fixo' => null,
                'porcentagem' => null,
            ];
            $final_format_update = ['%s', '%s', null, null, null];
        }


        $result = $wpdb->update(
            $table_name_profissionais,
            $final_data_update,
            array( 'id' => $profissional_id_update ), // WHERE
            $final_format_update, // Formato dos dados
            array( '%d' )  // Formato do WHERE
        );

        if ( $result === false ) {
            $error_redirect_url = admin_url( 'admin.php?page=agendamentos-wp-add-profissional&action=edit&profissional_id=' . $profissional_id_update );
            $error_redirect_url = add_query_arg( array(
                'agendamentos_wp_message' => __( 'Erro ao atualizar dados do profissional: ', 'agendamentos-wp' ) . $wpdb->last_error,
                'agendamentos_wp_message_type' => 'error',
            ), $error_redirect_url );
            wp_redirect( $error_redirect_url );
            exit;
        }

        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Profissional atualizado com sucesso!', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'success',
        ), $redirect_url ); // Redireciona para a lista de todos os profissionais

    } else {
        // CRIAR NOVO PROFISSIONAL
        if ( username_exists( $nome_usuario_wp ) ) {
            $error_redirect_url = admin_url( 'admin.php?page=agendamentos-wp-add-profissional' );
            $error_redirect_url = add_query_arg( array(
                'agendamentos_wp_message' => __( 'Erro: Este nome de usuário WP já existe.', 'agendamentos-wp' ),
                'agendamentos_wp_message_type' => 'error',
            ), $error_redirect_url );
            wp_redirect( $error_redirect_url );
            exit;
        }

        $user_id = wp_create_user( $nome_usuario_wp, $senha_wp );

        if ( is_wp_error( $user_id ) ) {
            $error_redirect_url = admin_url( 'admin.php?page=agendamentos-wp-add-profissional' );
            $error_redirect_url = add_query_arg( array(
                'agendamentos_wp_message' => __( 'Erro ao criar usuário WP: ', 'agendamentos-wp' ) . $user_id->get_error_message(),
                'agendamentos_wp_message_type' => 'error',
            ), $error_redirect_url );
            wp_redirect( $error_redirect_url );
            exit;
        }

        $u = new WP_User( $user_id );
        $u->set_role( 'profissionais' );

        $data_profissionais = array(
            'nome_profissional' => $nome_profissional,
            'telefone' => $telefone,
            'nome_usuario' => $nome_usuario_wp,
            'senha' => '', // Vazio
            'user_id' => $user_id,
        );

        if ( $adicionar_comissao ) {
            $data_profissionais['tipo_comissao'] = $tipo_comissao;
            if ( $tipo_comissao === 'valor_fixo' ) {
                $data_profissionais['valor_fixo'] = $valor_fixo_comissao;
                $data_profissionais['porcentagem'] = null;
            } elseif ( $tipo_comissao === 'porcentagem' ) {
                $data_profissionais['porcentagem'] = $porcentagem_comissao;
                $data_profissionais['valor_fixo'] = null;
            }
        }

        $format_profissionais = array('%s', '%s', '%s', '%s', '%d'); // nome_profissional, telefone, nome_usuario, senha, user_id
        if ($adicionar_comissao) {
            $format_profissionais[] = '%s'; // tipo_comissao
            if ($tipo_comissao === 'valor_fixo') {
                $format_profissionais[] = '%f'; // valor_fixo
            } else {
                $format_profissionais[] = null; // placeholder para valor_fixo
            }
            if ($tipo_comissao === 'porcentagem') {
                $format_profissionais[] = '%d'; // porcentagem
            } else {
                 $format_profissionais[] = null; // placeholder para porcentagem
            }
        } else {
            // Adiciona placeholders para os campos de comissão para manter a estrutura do array
            $format_profissionais[] = null;
            $format_profissionais[] = null;
            $format_profissionais[] = null;
        }
        // Remover formatos nulos para $wpdb->insert
        $final_insert_formats = [];
        foreach($format_profissionais as $f) {
            if(!is_null($f)) $final_insert_formats[] = $f;
        }


        $result = $wpdb->insert( $table_name_profissionais, $data_profissionais, $final_insert_formats );

        if ( $result === false ) {
            wp_delete_user( $user_id );
            $error_redirect_url = admin_url( 'admin.php?page=agendamentos-wp-add-profissional' );
            $error_redirect_url = add_query_arg( array(
                'agendamentos_wp_message' => __( 'Erro ao salvar dados do profissional no banco de dados: ', 'agendamentos-wp' ) . $wpdb->last_error,
                'agendamentos_wp_message_type' => 'error',
            ), $error_redirect_url );
            wp_redirect( $error_redirect_url );
            exit;
        }
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Profissional adicionado com sucesso! ID do Usuário WP: ', 'agendamentos-wp' ) . $user_id,
            'agendamentos_wp_message_type' => 'success',
        ), $redirect_url ); // Redireciona para a página de adicionar
    }

    wp_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_agendamentos_wp_save_profissional', 'agendamentos_wp_handle_save_profissional' );

/**
 * Lida com a exclusão de um profissional.
 */
function agendamentos_wp_handle_delete_profissional() {
    $profissional_id = isset( $_GET['profissional_id'] ) ? intval( $_GET['profissional_id'] ) : 0;
    $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';

    // 1. Validação e Segurança
    if ( ! wp_verify_nonce( $nonce, 'agendamentos_wp_delete_profissional_nonce_' . $profissional_id ) ) {
        wp_die( __( 'Falha na verificação de segurança (nonce) para exclusão.', 'agendamentos-wp' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) { // Ou uma capacidade mais específica para exclusão
        wp_die( __( 'Você não tem permissão para excluir profissionais.', 'agendamentos-wp' ) );
    }

    $redirect_url = admin_url( 'admin.php?page=agendamentos-wp-all-profissionais' );

    if ( $profissional_id <= 0 ) {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'ID do profissional inválido para exclusão.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }

    global $wpdb;
    $table_name_profissionais = $wpdb->prefix . 'profissionais';

    // Buscar o user_id do profissional antes de excluir da tabela personalizada
    $user_id_wp = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $table_name_profissionais WHERE id = %d", $profissional_id ) );

    // Excluir da tabela personalizada 'profissionais'
    $deleted_rows = $wpdb->delete( $table_name_profissionais, array( 'id' => $profissional_id ), array( '%d' ) );

    if ( $deleted_rows === false ) {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Erro ao excluir profissional da tabela personalizada: ', 'agendamentos-wp' ) . $wpdb->last_error,
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }

    if ( $deleted_rows > 0 && $user_id_wp ) {
        // Opcional: Excluir usuário WordPress associado
        // O terceiro argumento é para reatribuir posts, se houver.
        // Para este plugin, pode não ser necessário reatribuir, então null ou um ID de admin.
        // Se a role 'profissionais' não tiver permissão para criar posts, a reatribuição é menos crítica.
        $user_deleted = wp_delete_user( $user_id_wp, null ); // null para não reatribuir, ou ID de um admin

        if ( ! $user_deleted ) {
            // Mesmo se a exclusão do usuário WP falhar, o profissional da tabela personalizada foi removido.
            // Pode ser útil logar este erro ou notificar o admin de forma mais específica.
             $redirect_url = add_query_arg( array(
                'agendamentos_wp_message' => __( 'Profissional excluído da tabela, mas houve um problema ao excluir o usuário WP associado. Verifique os usuários do WordPress.', 'agendamentos-wp' ),
                'agendamentos_wp_message_type' => 'warning',
            ), $redirect_url );
            wp_redirect( $redirect_url );
            exit;
        }
    } elseif ($deleted_rows == 0) {
         $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Nenhum profissional encontrado com este ID para exclusão.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'warning',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }


    $redirect_url = add_query_arg( array(
        'agendamentos_wp_message' => __( 'Profissional excluído com sucesso!', 'agendamentos-wp' ),
        'agendamentos_wp_message_type' => 'success',
    ), $redirect_url );
    wp_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_agendamentos_wp_delete_profissional', 'agendamentos_wp_handle_delete_profissional' );

/**
 * Enfileira scripts e estilos para o painel administrativo.
 */
function agendamentos_wp_admin_enqueue_scripts( $hook_suffix ) {
    // Verificar se estamos nas páginas do plugin
    // Ex: 'toplevel_page_agendamentos-wp-main', 'agendamentos_page_agendamentos-wp-add-profissional', 'agendamentos_page_agendamentos-wp-all-profissionais'
    // O $hook_suffix para a página principal é toplevel_page_{slug_menu_principal}
    // Para submenus é {nome_do_plugin}_page_{slug_do_submenu} ou {slug_menu_principal}_page_{slug_do_submenu}

    $plugin_pages_suffixes = array(
        'toplevel_page_agendamentos-wp-main',
        'agendamentos_page_agendamentos-wp-add-profissional',
        'agendamentos_page_agendamentos-wp-all-profissionais',
        'agendamentos_page_agendamentos-wp-horarios',
        'agendamentos_page_agendamentos-wp-add-servico',
        'agendamentos_page_agendamentos-wp-all-servicos',
        'agendamentos_page_agendamentos-wp-clientes',
        'agendamentos_page_agendamentos-wp-view-agendamentos', // Adicionando a nova página de visualização de agendamentos
    );

    // Tenta identificar o hook correto para submenus.
    // O WordPress pode prefixar o hook com o nome da função do menu principal ou o text domain.
    // Adicionando variações comuns.
    if (strpos($hook_suffix, 'agendamentos-wp-main_page_agendamentos-wp-add-profissional') !== false) {
        $plugin_pages_suffixes[] = $hook_suffix;
    }
    if (strpos($hook_suffix, 'agendamentos-wp-main_page_agendamentos-wp-all-profissionais') !== false) {
        $plugin_pages_suffixes[] = $hook_suffix;
    }
    if (strpos($hook_suffix, 'agendamentos-wp-main_page_agendamentos-wp-horarios') !== false) {
        $plugin_pages_suffixes[] = $hook_suffix;
    }
    if (strpos($hook_suffix, 'agendamentos-wp-main_page_agendamentos-wp-add-servico') !== false) {
        $plugin_pages_suffixes[] = $hook_suffix;
    }
    if (strpos($hook_suffix, 'agendamentos-wp-main_page_agendamentos-wp-all-servicos') !== false) {
        $plugin_pages_suffixes[] = $hook_suffix;
    }
    if (strpos($hook_suffix, 'agendamentos-wp-main_page_agendamentos-wp-clientes') !== false) {
        $plugin_pages_suffixes[] = $hook_suffix;
    }
    if (strpos($hook_suffix, 'agendamentos-wp-main_page_agendamentos-wp-view-agendamentos') !== false) { // Para a nova página
        $plugin_pages_suffixes[] = $hook_suffix;
    }


    // Para garantir que o CSS seja carregado apenas nas páginas do plugin:
    // 1. Obtenha o slug da página atual
    $current_screen = get_current_screen();
    if ( $current_screen && in_array( $current_screen->id, $plugin_pages_suffixes ) ) {
        wp_enqueue_style(
            'agendamentos-wp-admin-styles', // Handle
            plugin_dir_url( __FILE__ ) . 'assets/css/admin-styles.css', // Source
            array(), // Dependencies
            '0.1.0' // Version
        );
    }
}
add_action( 'admin_enqueue_scripts', 'agendamentos_wp_admin_enqueue_scripts' );


/**
 * Adiciona os itens de menu no painel administrativo do WordPress.
 */
function agendamentos_wp_admin_menu() {
    add_menu_page(
        __( 'Agendamentos', 'agendamentos-wp' ),
        __( 'Agendamentos', 'agendamentos-wp' ),
        'manage_options',
        'agendamentos-wp-main',
        'agendamentos_wp_main_page_render',
        'dashicons-calendar-alt',
        26
    );

    add_submenu_page(
        'agendamentos-wp-main',
        __( 'Todos os Profissionais', 'agendamentos-wp' ),
        __( 'Todos os Profissionais', 'agendamentos-wp' ),
        'manage_options',
        'agendamentos-wp-all-profissionais',
        'agendamentos_wp_all_profissionais_page_render'
    );

    add_submenu_page(
        'agendamentos-wp-main',
        __( 'Adicionar Novo Profissional', 'agendamentos-wp' ),
        __( 'Adicionar Profissional', 'agendamentos-wp' ),
        'manage_options',
        'agendamentos-wp-add-profissional',
        'agendamentos_wp_add_profissional_page_render'
    );

    add_submenu_page(
        'agendamentos-wp-main',
        __( 'Configurar Horários', 'agendamentos-wp' ),
        __( 'Horários', 'agendamentos-wp' ),
        'manage_options',
        'agendamentos-wp-horarios',
        'agendamentos_wp_horarios_page_render'
    );

    add_submenu_page(
        'agendamentos-wp-main',
        __( 'Adicionar Novo Serviço', 'agendamentos-wp' ),
        __( 'Adicionar Serviço', 'agendamentos-wp' ),
        'manage_options',
        'agendamentos-wp-add-servico',
        'agendamentos_wp_add_servico_page_render'
    );

    add_submenu_page(
        'agendamentos-wp-main',
        __( 'Todos os Serviços', 'agendamentos-wp' ),
        __( 'Todos os Serviços', 'agendamentos-wp' ),
        'manage_options',
        'agendamentos-wp-all-servicos',
        'agendamentos_wp_all_servicos_page_render'
    );

    add_submenu_page(
        'agendamentos-wp-main',
        __( 'Clientes', 'agendamentos-wp' ),
        __( 'Clientes', 'agendamentos-wp' ),
        'manage_options',
        'agendamentos-wp-clientes',
        'agendamentos_wp_clientes_page_render'
    );

    add_submenu_page(
        'agendamentos-wp-main',
        __( 'Visualizar Agendamentos', 'agendamentos-wp' ),
        __( 'Agendamentos', 'agendamentos-wp' ), // Título do Menu
        'manage_options',
        'agendamentos-wp-view-agendamentos',
        'agendamentos_wp_view_agendamentos_page_render'
    );
}
// A ação admin_menu é registrada uma vez após a definição completa da função.
// Se já existe um remove_action/add_action para agendamentos_wp_admin_menu, ele cuidará disso.
// Se não, e esta é a última adição de submenu, o remove/add deve estar após esta função.
// Por segurança e clareza, como a função é modificada incrementalmente,
// garantir que ela seja removida e adicionada uma vez após todas as adições de submenu é o ideal.
// No entanto, o código anterior já faz isso.


/**
 * Renderiza a página de configuração de horários.
 */
function agendamentos_wp_horarios_page_render() {
    global $wpdb;
    $table_name_profissionais = $wpdb->prefix . 'profissionais';
    $profissionais = $wpdb->get_results( "SELECT id, nome_profissional FROM $table_name_profissionais ORDER BY nome_profissional ASC" );
    $selected_profissional_id = isset( $_GET['profissional_id_horarios'] ) ? intval( $_GET['profissional_id_horarios'] ) : 0;

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Configurar Horários dos Profissionais', 'agendamentos-wp' ); ?></h1>

        <form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
            <input type="hidden" name="page" value="agendamentos-wp-horarios">
            <p>
                <label for="profissional_id_horarios"><?php esc_html_e( 'Selecione o Profissional:', 'agendamentos-wp' ); ?></label>
                <select name="profissional_id_horarios" id="profissional_id_horarios">
                    <option value="0"><?php esc_html_e( '-- Selecione --', 'agendamentos-wp' ); ?></option>
                    <?php foreach ( $profissionais as $profissional ) : ?>
                        <option value="<?php echo esc_attr( $profissional->id ); ?>" <?php selected( $selected_profissional_id, $profissional->id ); ?>>
                            <?php echo esc_html( $profissional->nome_profissional ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="<?php esc_attr_e( 'Carregar Horários', 'agendamentos-wp' ); ?>" class="button">
            </p>
        </form>

        <?php if ( $selected_profissional_id > 0 ) : ?>
            <?php
            $profissional_selecionado = $wpdb->get_row( $wpdb->prepare( "SELECT nome_profissional FROM $table_name_profissionais WHERE id = %d", $selected_profissional_id ) );
            ?>
            <h2><?php printf( esc_html__( 'Horários para: %s', 'agendamentos-wp' ), esc_html( $profissional_selecionado->nome_profissional ) ); ?></h2>

            <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="agendamentos_wp_save_horarios">
                <input type="hidden" name="profissional_id" value="<?php echo esc_attr( $selected_profissional_id ); ?>">
                <?php wp_nonce_field( 'agendamentos_wp_save_horarios_action', 'agendamentos_wp_save_horarios_nonce' ); ?>

                <div class="horarios-cards-container">
                    <?php
                    $dias_semana = array(
                        1 => __( 'Segunda-feira', 'agendamentos-wp' ),
                        2 => __( 'Terça-feira', 'agendamentos-wp' ),
                        3 => __( 'Quarta-feira', 'agendamentos-wp' ),
                        4 => __( 'Quinta-feira', 'agendamentos-wp' ),
                        5 => __( 'Sexta-feira', 'agendamentos-wp' ),
                        6 => __( 'Sábado', 'agendamentos-wp' ),
                        0 => __( 'Domingo', 'agendamentos-wp' ),
                    );

                    $table_name_horarios = $wpdb->prefix . 'horarios';
                    $horarios_salvos = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name_horarios WHERE profissional_id = %d", $selected_profissional_id ), OBJECT_K );

                    // Garantir que a chave seja dia_semana para fácil acesso
                    $horarios_por_dia = array();
                    foreach ($horarios_salvos as $h) {
                        $horarios_por_dia[$h->dia_semana] = $h;
                    }

                    foreach ( $dias_semana as $dia_index => $nome_dia ) :
                        $horario_dia = isset( $horarios_por_dia[ $dia_index ] ) ? $horarios_por_dia[ $dia_index ] : null;
                        $dia_folga = $horario_dia && $horario_dia->dia_folga == 1;
                        $horario_inicio = $horario_dia ? $horario_dia->horario_inicio : '';
                        $horario_termino = $horario_dia ? $horario_dia->horario_termino : '';
                        $inicio_almoco = $horario_dia ? $horario_dia->inicio_almoco : '';
                        $termino_almoco = $horario_dia ? $horario_dia->termino_almoco : '';
                        $pausa_entre_atendimentos = $horario_dia ? $horario_dia->pausa_entre_atendimentos : '';
                    ?>
                    <div class="horario-card" id="card-dia-<?php echo esc_attr($dia_index); ?>">
                        <h3><?php echo esc_html( $nome_dia ); ?></h3>
                        <div class="field-group">
                            <label for="dia_folga_<?php echo esc_attr($dia_index); ?>">
                                <input type="checkbox"
                                       id="dia_folga_<?php echo esc_attr($dia_index); ?>"
                                       name="horarios[<?php echo esc_attr($dia_index); ?>][dia_folga]"
                                       value="1" <?php checked( $dia_folga ); ?>
                                       onchange="toggleHorarioFields(this, <?php echo esc_attr($dia_index); ?>)">
                                <?php esc_html_e( 'Dia de Folga', 'agendamentos-wp' ); ?>
                            </label>
                        </div>

                        <div class="horario-fields-group" id="fields-group-<?php echo esc_attr($dia_index); ?>" style="<?php echo $dia_folga ? 'display:none;' : ''; ?>">
                            <div class="field-group">
                                <label for="horario_inicio_<?php echo esc_attr($dia_index); ?>"><?php esc_html_e( 'Horário Início:', 'agendamentos-wp' ); ?></label>
                                <input type="time" id="horario_inicio_<?php echo esc_attr($dia_index); ?>" name="horarios[<?php echo esc_attr($dia_index); ?>][horario_inicio]" value="<?php echo esc_attr( $horario_inicio ); ?>">
                            </div>
                            <div class="field-group">
                                <label for="horario_termino_<?php echo esc_attr($dia_index); ?>"><?php esc_html_e( 'Horário Término:', 'agendamentos-wp' ); ?></label>
                                <input type="time" id="horario_termino_<?php echo esc_attr($dia_index); ?>" name="horarios[<?php echo esc_attr($dia_index); ?>][horario_termino]" value="<?php echo esc_attr( $horario_termino ); ?>">
                            </div>
                            <hr>
                            <div class="field-group">
                                <label for="inicio_almoco_<?php echo esc_attr($dia_index); ?>"><?php esc_html_e( 'Início Almoço:', 'agendamentos-wp' ); ?></label>
                                <input type="time" id="inicio_almoco_<?php echo esc_attr($dia_index); ?>" name="horarios[<?php echo esc_attr($dia_index); ?>][inicio_almoco]" value="<?php echo esc_attr( $inicio_almoco ); ?>">
                            </div>
                            <div class="field-group">
                                <label for="termino_almoco_<?php echo esc_attr($dia_index); ?>"><?php esc_html_e( 'Término Almoço:', 'agendamentos-wp' ); ?></label>
                                <input type="time" id="termino_almoco_<?php echo esc_attr($dia_index); ?>" name="horarios[<?php echo esc_attr($dia_index); ?>][termino_almoco]" value="<?php echo esc_attr( $termino_almoco ); ?>">
                            </div>
                            <hr>
                            <div class="field-group">
                                <label for="pausa_entre_atendimentos_<?php echo esc_attr($dia_index); ?>"><?php esc_html_e( 'Pausa entre Atendimentos (minutos):', 'agendamentos-wp' ); ?></label>
                                <input type="number" id="pausa_entre_atendimentos_<?php echo esc_attr($dia_index); ?>" name="horarios[<?php echo esc_attr($dia_index); ?>][pausa_entre_atendimentos]" value="<?php echo esc_attr( $pausa_entre_atendimentos ); ?>" min="0">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php submit_button( __( 'Salvar Horários', 'agendamentos-wp' ) ); ?>
            </form>

            <script type="text/javascript">
                function toggleHorarioFields(checkbox, diaIndex) {
                    var fieldsGroup = document.getElementById('fields-group-' + diaIndex);
                    if (checkbox.checked) {
                        fieldsGroup.style.display = 'none';
                    } else {
                        fieldsGroup.style.display = 'block';
                    }
                }
                 // Garantir que o estado inicial seja respeitado no carregamento da página
                document.addEventListener('DOMContentLoaded', function() {
                    <?php foreach ( $dias_semana as $dia_index => $nome_dia ) : ?>
                        var checkbox_<?php echo esc_js($dia_index); ?> = document.getElementById('dia_folga_<?php echo esc_js($dia_index); ?>');
                        if (checkbox_<?php echo esc_js($dia_index); ?>) { // Verifica se o elemento existe
                           // toggleHorarioFields(checkbox_<?php echo esc_js($dia_index); ?>, <?php echo esc_js($dia_index); ?>);
                           // O estilo inline já cuida do estado inicial, o JS só precisa lidar com as mudanças.
                        }
                    <?php endforeach; ?>
                });
            </script>

        <?php endif; ?>
    </div>
    <?php
}

/**
 * Renderiza a página para adicionar um novo serviço.
 */
function agendamentos_wp_add_servico_page_render() {
    global $wpdb;
    $table_name_profissionais = $wpdb->prefix . 'profissionais';
    $profissionais = $wpdb->get_results( "SELECT id, nome_profissional FROM $table_name_profissionais ORDER BY nome_profissional ASC" );

    // Valores padrão para o formulário (para evitar undefined notices)
    $form_data = array(
        'nome_servico' => '',
        'descricao_servico' => '',
        'duracao_servico' => '',
        'preco_servico' => '',
        'profissionais_servico' => array(), // Usado para pré-selecionar checkboxes na edição
        'exigir_sinal_servico' => false,
        'porcentagem_sinal_servico' => '',
    );

    $servico_id_edit = isset( $_GET['servico_id'] ) ? intval( $_GET['servico_id'] ) : 0;
    $action_edit = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'add';
    $is_edit_mode = ( $action_edit === 'edit' && $servico_id_edit > 0 );

    $page_title = $is_edit_mode ? __( 'Editar Serviço', 'agendamentos-wp' ) : __( 'Adicionar Novo Serviço', 'agendamentos-wp' );
    $submit_button_text = $is_edit_mode ? __( 'Atualizar Serviço', 'agendamentos-wp' ) : __( 'Salvar Serviço', 'agendamentos-wp' );

    // Valores padrão para o formulário
    $form_data = array(
        'nome_servico' => '',
        'descricao_servico' => '',
        'duracao_servico' => '',
        'preco_servico' => '',
        'profissionais_servico' => array(),
        'exigir_sinal_servico' => false,
        'porcentagem_sinal_servico' => '',
    );

    if ( $is_edit_mode ) {
        // Verificar nonce para edição
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'agendamentos_wp_edit_servico_nonce_' . $servico_id_edit ) ) {
            wp_die( __( 'Falha na verificação de segurança (nonce) para edição de serviço.', 'agendamentos-wp' ) );
        }

        $table_name_servicos = $wpdb->prefix . 'servicos';
        $servico_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name_servicos WHERE id = %d", $servico_id_edit ) );

        if ( $servico_data ) {
            $form_data['nome_servico'] = $servico_data->nome_servico;
            $form_data['descricao_servico'] = $servico_data->descricao;
            $form_data['duracao_servico'] = $servico_data->duracao;
            $form_data['preco_servico'] = $servico_data->preco;
            $form_data['exigir_sinal_servico'] = (bool) $servico_data->exigir_sinal;
            $form_data['porcentagem_sinal_servico'] = $servico_data->porcentagem_sinal;

            // Buscar profissionais associados
            $table_name_sp = $wpdb->prefix . 'servicos_profissionais';
            $profissionais_associados_results = $wpdb->get_results( $wpdb->prepare( "SELECT profissional_id FROM $table_name_sp WHERE servico_id = %d", $servico_id_edit ) );
            foreach ( $profissionais_associados_results as $assoc ) {
                $form_data['profissionais_servico'][] = $assoc->profissional_id;
            }
        } else {
            // Serviço não encontrado, reverter para modo de adição ou mostrar erro
            $is_edit_mode = false;
            $page_title = __( 'Adicionar Novo Serviço', 'agendamentos-wp' );
            $submit_button_text = __( 'Salvar Serviço', 'agendamentos-wp' );
            // Adicionar uma admin notice aqui seria bom
            echo '<div class="notice notice-error"><p>' . esc_html__('Serviço não encontrado para edição.', 'agendamentos-wp') . '</p></div>';
        }
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( $page_title ); ?></h1>
        <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="agendamentos_wp_save_servico">
            <?php wp_nonce_field( 'agendamentos_wp_save_servico_action', 'agendamentos_wp_save_servico_nonce' ); ?>
            <?php if ( $is_edit_mode ) : ?>
                <input type="hidden" name="servico_id_update" value="<?php echo esc_attr( $servico_id_edit ); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="nome_servico"><?php esc_html_e( 'Nome do Serviço', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="nome_servico" name="nome_servico" class="regular-text" value="<?php echo esc_attr( $form_data['nome_servico'] ); ?>" required />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="descricao_servico"><?php esc_html_e( 'Descrição', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <textarea id="descricao_servico" name="descricao_servico" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $form_data['descricao_servico'] ); ?></textarea>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="duracao_servico"><?php esc_html_e( 'Duração (minutos)', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="duracao_servico" name="duracao_servico" class="small-text" value="<?php echo esc_attr( $form_data['duracao_servico'] ); ?>" required min="1" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="preco_servico"><?php esc_html_e( 'Preço (R$)', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="number" step="0.01" id="preco_servico" name="preco_servico" class="small-text" value="<?php echo esc_attr( $form_data['preco_servico'] ); ?>" required min="0" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Profissionais que realizam o serviço', 'agendamentos-wp' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php esc_html_e( 'Profissionais', 'agendamentos-wp' ); ?></span></legend>
                            <?php if ( ! empty( $profissionais ) ) : ?>
                                <?php foreach ( $profissionais as $profissional ) : ?>
                                    <label for="profissional_servico_<?php echo esc_attr( $profissional->id ); ?>">
                                        <input type="checkbox"
                                               id="profissional_servico_<?php echo esc_attr( $profissional->id ); ?>"
                                               name="profissionais_servico[]"
                                               value="<?php echo esc_attr( $profissional->id ); ?>"
                                               <?php checked( in_array( $profissional->id, $form_data['profissionais_servico'] ) ); ?>>
                                        <?php echo esc_html( $profissional->nome_profissional ); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p><?php esc_html_e( 'Nenhum profissional cadastrado. Adicione profissionais primeiro.', 'agendamentos-wp' ); ?></p>
                            <?php endif; ?>
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Sinal', 'agendamentos-wp' ); ?></th>
                    <td>
                        <label for="toggle_sinal_servico">
                            <input type="checkbox" id="toggle_sinal_servico" name="exigir_sinal_servico" value="1" <?php checked( $form_data['exigir_sinal_servico'] ); ?>>
                            <?php esc_html_e( 'Exigir Sinal?', 'agendamentos-wp' ); ?>
                        </label>
                    </td>
                </tr>
                <tr valign="top" class="campo-porcentagem-sinal" style="<?php echo $form_data['exigir_sinal_servico'] ? '' : 'display: none;'; ?>">
                    <th scope="row">
                        <label for="porcentagem_sinal_servico"><?php esc_html_e( 'Porcentagem do Sinal (%)', 'agendamentos-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="porcentagem_sinal_servico" name="porcentagem_sinal_servico" class="small-text" value="<?php echo esc_attr( $form_data['porcentagem_sinal_servico'] ); ?>" min="1" max="100" />
                    </td>
                </tr>
            </table>

            <?php submit_button( $submit_button_text, 'primary', 'submit_servico' ); ?>
        </form>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Script para o toggle de sinal na página de Adicionar Serviço
            $('#toggle_sinal_servico').on('change', function() {
                var $campoPorcentagem = $('.campo-porcentagem-sinal');
                var $inputPorcentagem = $('#porcentagem_sinal_servico');
                if ($(this).is(':checked')) {
                    $campoPorcentagem.show();
                    // Opcional: tornar o campo porcentagem obrigatório se o sinal for exigido
                    // $inputPorcentagem.prop('required', true);
                } else {
                    $campoPorcentagem.hide();
                    // $inputPorcentagem.prop('required', false);
                }
            });
            // Trigger inicial para garantir o estado correto ao carregar a página (especialmente para edição)
             if ($('#toggle_sinal_servico').length) {
                $('#toggle_sinal_servico').trigger('change');
            }


            // Script para o toggle de comissão na página de Adicionar Profissional (já existente e isolado)
            var toggleComissaoCheckbox = $('#toggle_comissao');
            if (toggleComissaoCheckbox.length) {
                function toggleComissaoFieldsProfissional() {
                    if (toggleComissaoCheckbox.is(':checked')) {
                        $('.comissao-fields').show();
                        var tipoComissao = $('#tipo_comissao').val();
                        if (tipoComissao === 'valor_fixo') {
                            $('.comissao-valor-fixo-field').show();
                            $('.comissao-porcentagem-field').hide();
                        } else if (tipoComissao === 'porcentagem') {
                            $('.comissao-valor-fixo-field').hide();
                            $('.comissao-porcentagem-field').show();
                        }
                    } else {
                        $('.comissao-fields').hide();
                        $('.comissao-valor-fixo-field').hide();
                        $('.comissao-porcentagem-field').hide();
                    }
                }
                toggleComissaoCheckbox.on('change', toggleComissaoFieldsProfissional);
                $('#tipo_comissao').on('change', toggleComissaoFieldsProfissional);
                toggleComissaoFieldsProfissional();
            }
        });
    </script>
    <?php
}

/**
 * Renderiza a página "Todos os Serviços".
 */
function agendamentos_wp_all_servicos_page_render() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Todos os Serviços', 'agendamentos-wp' ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=agendamentos-wp-add-servico' ) ); ?>" class="page-title-action">
                <?php esc_html_e( 'Adicionar Novo', 'agendamentos-wp' ); ?>
            </a>
        </h1>

        <?php
        global $wpdb;
        $table_name_servicos = $wpdb->prefix . 'servicos';
        $table_name_profissionais = $wpdb->prefix . 'profissionais';
        $table_name_servicos_profissionais = $wpdb->prefix . 'servicos_profissionais';

        // Query otimizada para buscar serviços e nomes de profissionais associados
        $query_servicos = "
            SELECT s.*, GROUP_CONCAT(p.nome_profissional SEPARATOR ', ') as nomes_profissionais
            FROM {$table_name_servicos} s
            LEFT JOIN {$table_name_servicos_profissionais} sp ON s.id = sp.servico_id
            LEFT JOIN {$table_name_profissionais} p ON sp.profissional_id = p.id
            GROUP BY s.id
            ORDER BY s.nome_servico ASC
        ";
        $servicos = $wpdb->get_results( $query_servicos );
        ?>

        <div class="servicos-list-table-wrapper">
            <table class="wp-list-table widefat fixed striped table-view-list posts">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e( 'Nome do Serviço', 'agendamentos-wp' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Profissionais', 'agendamentos-wp' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Preço', 'agendamentos-wp' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Duração', 'agendamentos-wp' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Ações', 'agendamentos-wp' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list-servicos">
                    <?php if ( $servicos ) : ?>
                        <?php foreach ( $servicos as $servico ) : ?>
                            <?php
                            $edit_nonce_servico = wp_create_nonce( 'agendamentos_wp_edit_servico_nonce_' . $servico->id );
                            $delete_nonce_servico = wp_create_nonce( 'agendamentos_wp_delete_servico_nonce_' . $servico->id );
                            $edit_link_servico = admin_url( 'admin.php?page=agendamentos-wp-add-servico&action=edit&servico_id=' . $servico->id . '&_wpnonce=' . $edit_nonce_servico );
                            $delete_link_base_servico = admin_url( 'admin-post.php' );
														$delete_link_servico = add_query_arg( array(
																'action' => 'agendamentos_wp_delete_servico',
																'servico_id' => $servico->id,
																'_wpnonce' => $delete_nonce_servico
														), $delete_link_base_servico );
                            ?>
                            <tr>
                                <td class="column-primary">
                                    <strong><?php echo esc_html( $servico->nome_servico ); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit"><a href="<?php echo esc_url( $edit_link_servico ); ?>"><?php esc_html_e( 'Editar', 'agendamentos-wp' ); ?></a> | </span>
                                        <span class="delete"><a href="<?php echo esc_url( $delete_link_servico ); ?>" onclick="return confirm('<?php esc_attr_e( 'Tem certeza que deseja excluir este serviço? As associações com profissionais também serão removidas.', 'agendamentos-wp' ); ?>');"><?php esc_html_e( 'Excluir', 'agendamentos-wp' ); ?></a></span>
                                    </div>
                                </td>
                                <td><?php echo esc_html( $servico->nomes_profissionais ? $servico->nomes_profissionais : __( 'Nenhum', 'agendamentos-wp' ) ); ?></td>
                                <td>R$ <?php echo esc_html( number_format_i18n( $servico->preco, 2 ) ); ?></td>
                                <td><?php echo esc_html( $servico->duracao ); ?> min</td>
                                <td>
                                    <a href="<?php echo esc_url( $edit_link_servico ); ?>" class="button button-small"><?php esc_html_e( 'Editar', 'agendamentos-wp' ); ?></a>
                                    <a href="<?php echo esc_url( $delete_link_servico ); ?>" class="button button-small delete-button" onclick="return confirm('<?php esc_attr_e( 'Tem certeza que deseja excluir este serviço? As associações com profissionais também serão removidas.', 'agendamentos-wp' ); ?>');"><?php esc_html_e( 'Excluir', 'agendamentos-wp' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'Nenhum serviço encontrado.', 'agendamentos-wp' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="servicos-list-mobile" style="display:none;">
            <ul>
            <?php if ( $servicos ) : ?>
                <?php foreach ( $servicos as $servico ) : ?>
                     <?php
                        $edit_nonce_servico_mobile = wp_create_nonce( 'agendamentos_wp_edit_servico_nonce_mobile_' . $servico->id );
                        $delete_nonce_servico_mobile = wp_create_nonce( 'agendamentos_wp_delete_servico_nonce_mobile_' . $servico->id );
                        $edit_link_servico_mobile = admin_url( 'admin.php?page=agendamentos-wp-add-servico&action=edit&servico_id=' . $servico->id . '&_wpnonce=' . $edit_nonce_servico_mobile );
                        $delete_link_base_servico_mobile = admin_url( 'admin-post.php' );
												$delete_link_servico_mobile = add_query_arg( array(
														'action' => 'agendamentos_wp_delete_servico',
														'servico_id' => $servico->id,
														'_wpnonce' => $delete_nonce_servico_mobile
												), $delete_link_base_servico_mobile );
                    ?>
                    <li>
                        <strong><?php esc_html_e( 'Serviço:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $servico->nome_servico ); ?><br>
                        <div class="servico-profissionais"><strong><?php esc_html_e( 'Profissionais:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $servico->nomes_profissionais ? $servico->nomes_profissionais : __( 'Nenhum', 'agendamentos-wp' ) ); ?></div>
                        <strong><?php esc_html_e( 'Preço:', 'agendamentos-wp' ); ?></strong> R$ <?php echo esc_html( number_format_i18n( $servico->preco, 2 ) ); ?><br>
                        <strong><?php esc_html_e( 'Duração:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $servico->duracao ); ?> min<br>
                        <a href="<?php echo esc_url( $edit_link_servico_mobile ); ?>"><?php esc_html_e( 'Editar', 'agendamentos-wp' ); ?></a> |
                        <a href="<?php echo esc_url( $delete_link_servico_mobile ); ?>" onclick="return confirm('<?php esc_attr_e( 'Tem certeza que deseja excluir este serviço? As associações com profissionais também serão removidas.', 'agendamentos-wp' ); ?>');"><?php esc_html_e( 'Excluir', 'agendamentos-wp' ); ?></a>
                    </li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php esc_html_e( 'Nenhum serviço encontrado.', 'agendamentos-wp' ); ?></li>
            <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Renderiza a página de visualização de Agendamentos.
 */
function agendamentos_wp_view_agendamentos_page_render() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Visualizar Agendamentos', 'agendamentos-wp' ); ?></h1>

        <?php
        global $wpdb;
        // Buscar profissionais para o filtro
        $profissionais = $wpdb->get_results( "SELECT id, nome_profissional FROM {$wpdb->prefix}profissionais ORDER BY nome_profissional ASC" );
        $status_list = array( 'pendente', 'confirmado', 'cancelado', 'concluido' );

        // Obter valores dos filtros (se houver)
        $filter_profissional_id = isset( $_GET['profissional_id_filter'] ) ? intval( $_GET['profissional_id_filter'] ) : '';
        $filter_status = isset( $_GET['status_filter'] ) ? sanitize_text_field( $_GET['status_filter'] ) : '';
        $filter_data = isset( $_GET['data_filter'] ) ? sanitize_text_field( $_GET['data_filter'] ) : '';

        // Construir a query base
        $query_base = "
            SELECT
                a.id,
                s.nome_servico,
                p.nome_profissional,
                c.nome_cliente,
                c.telefone AS cliente_telefone,
                a.data_agendamento,
                a.hora_agendamento,
                a.total,
                a.status
            FROM
                {$wpdb->prefix}agendamentos a
            LEFT JOIN
                {$wpdb->prefix}servicos s ON a.servico_id = s.id
            LEFT JOIN
                {$wpdb->prefix}profissionais p ON a.profissional_id = p.id
            LEFT JOIN
                {$wpdb->prefix}clientes c ON a.cliente_id = c.id
        ";

        $where_clauses = array();
        $params = array();

        if ( !empty( $filter_profissional_id ) ) {
            $where_clauses[] = "a.profissional_id = %d";
            $params[] = $filter_profissional_id;
        }
        if ( !empty( $filter_status ) ) {
            $where_clauses[] = "a.status = %s";
            $params[] = $filter_status;
        }
        if ( !empty( $filter_data ) ) {
            // Validar o formato da data YYYY-MM-DD
            if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $filter_data)) {
                $where_clauses[] = "a.data_agendamento = %s";
                $params[] = $filter_data;
            } else {
                 echo '<div class="notice notice-error"><p>' . esc_html__('Formato de data inválido para o filtro.', 'agendamentos-wp') . '</p></div>';
                 $filter_data = ''; // Limpa o filtro de data se inválido
            }
        }

        if ( !empty( $where_clauses ) ) {
            $query_base .= " WHERE " . implode( " AND ", $where_clauses );
        }

        $query_base .= " ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC";

        $agendamentos = $wpdb->get_results( empty($params) ? $query_base : $wpdb->prepare( $query_base, $params ) );

        ?>

        <div class="agendamentos-filters">
            <form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                <input type="hidden" name="page" value="agendamentos-wp-view-agendamentos">
                <div class="filter-item">
                    <label for="profissional_id_filter"><?php esc_html_e( 'Profissional:', 'agendamentos-wp' ); ?></label>
                    <select name="profissional_id_filter" id="profissional_id_filter">
                        <option value=""><?php esc_html_e( 'Todos', 'agendamentos-wp' ); ?></option>
                        <?php foreach ( $profissionais as $prof ) : ?>
                            <option value="<?php echo esc_attr( $prof->id ); ?>" <?php selected( $filter_profissional_id, $prof->id ); ?>>
                                <?php echo esc_html( $prof->nome_profissional ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="status_filter"><?php esc_html_e( 'Status:', 'agendamentos-wp' ); ?></label>
                    <select name="status_filter" id="status_filter">
                        <option value=""><?php esc_html_e( 'Todos', 'agendamentos-wp' ); ?></option>
                        <?php foreach ( $status_list as $status_item ) : ?>
                            <option value="<?php echo esc_attr( $status_item ); ?>" <?php selected( $filter_status, $status_item ); ?>>
                                <?php echo esc_html( ucfirst( $status_item ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="data_filter"><?php esc_html_e( 'Data:', 'agendamentos-wp' ); ?></label>
                    <input type="date" name="data_filter" id="data_filter" value="<?php echo esc_attr( $filter_data ); ?>">
                </div>
                <div class="filter-item">
                    <input type="submit" value="<?php esc_attr_e( 'Filtrar', 'agendamentos-wp' ); ?>" class="button">
                     <a href="<?php echo esc_url( admin_url( 'admin.php?page=agendamentos-wp-view-agendamentos' ) ); ?>" class="button"><?php esc_html_e( 'Limpar Filtros', 'agendamentos-wp' ); ?></a>
                </div>
            </form>
        </div>


        <div class="agendamentos-list-table-wrapper">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Serviço', 'agendamentos-wp' ); ?></th>
                        <th><?php esc_html_e( 'Profissional', 'agendamentos-wp' ); ?></th>
                        <th><?php esc_html_e( 'Cliente', 'agendamentos-wp' ); ?></th>
                        <th><?php esc_html_e( 'Data', 'agendamentos-wp' ); ?></th>
                        <th><?php esc_html_e( 'Hora', 'agendamentos-wp' ); ?></th>
                        <th><?php esc_html_e( 'Total', 'agendamentos-wp' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'agendamentos-wp' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list-agendamentos">
                    <?php if ( $agendamentos ) : ?>
                        <?php foreach ( $agendamentos as $agendamento ) : ?>
                            <tr>
                                <td><?php echo esc_html( $agendamento->nome_servico ); ?></td>
                                <td><?php echo esc_html( $agendamento->nome_profissional ); ?></td>
                                <td>
                                    <?php echo esc_html( $agendamento->nome_cliente ); ?><br>
                                    <small><?php echo esc_html( $agendamento->cliente_telefone ); ?></small>
                                </td>
                                <td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $agendamento->data_agendamento ) ); ?></td>
                                <td><?php echo esc_html( substr($agendamento->hora_agendamento, 0, 5) ); ?></td>
                                <td>R$ <?php echo esc_html( number_format_i18n( $agendamento->total, 2 ) ); ?></td>
                                <td><span class="status-badge status-<?php echo esc_attr($agendamento->status); ?>"><?php echo esc_html( ucfirst($agendamento->status) ); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7"><?php esc_html_e( 'Nenhum agendamento encontrado com os filtros aplicados.', 'agendamentos-wp' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="agendamentos-list-mobile" style="display:none;">
            <ul>
            <?php if ( $agendamentos ) : ?>
                <?php foreach ( $agendamentos as $agendamento ) : ?>
                    <li>
                        <strong><?php esc_html_e( 'Serviço:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $agendamento->nome_servico ); ?><br>
                        <strong><?php esc_html_e( 'Profissional:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $agendamento->nome_profissional ); ?><br>
                        <div class="agendamento-details">
                            <strong><?php esc_html_e( 'Cliente:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $agendamento->nome_cliente ); ?> (<?php echo esc_html( $agendamento->cliente_telefone ); ?>)<br>
                            <strong><?php esc_html_e( 'Data:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ), $agendamento->data_agendamento ) ); ?> às <?php echo esc_html( substr($agendamento->hora_agendamento, 0, 5) ); ?><br>
                            <strong><?php esc_html_e( 'Total:', 'agendamentos-wp' ); ?></strong> R$ <?php echo esc_html( number_format_i18n( $agendamento->total, 2 ) ); ?><br>
                            <strong><?php esc_html_e( 'Status:', 'agendamentos-wp' ); ?></strong> <span class="status-badge status-<?php echo esc_attr($agendamento->status); ?>"><?php echo esc_html( ucfirst($agendamento->status) ); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else : ?>
                 <li><?php esc_html_e( 'Nenhum agendamento encontrado com os filtros aplicados.', 'agendamentos-wp' ); ?></li>
            <?php endif; ?>
            </ul>
        </div>

    </div>
    <?php
}


/**
 * Renderiza a página de Clientes (formulário de adição e listagem).
 */
function agendamentos_wp_clientes_page_render() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Clientes', 'agendamentos-wp' ); ?></h1>

        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <h2><?php esc_html_e( 'Adicionar Novo Cliente', 'agendamentos-wp' ); ?></h2>
                    <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="form-wrap">
                        <input type="hidden" name="action" value="agendamentos_wp_save_cliente">
                        <?php wp_nonce_field( 'agendamentos_wp_save_cliente_action', 'agendamentos_wp_save_cliente_nonce' ); ?>

                        <div class="form-field form-required">
                            <label for="nome_cliente"><?php esc_html_e( 'Nome do Cliente', 'agendamentos-wp' ); ?></label>
                            <input type="text" name="nome_cliente" id="nome_cliente" value="" required aria-required="true" />
                        </div>
                        <div class="form-field form-required">
                            <label for="telefone_cliente"><?php esc_html_e( 'Telefone', 'agendamentos-wp' ); ?></label>
                            <input type="tel" name="telefone_cliente" id="telefone_cliente" value="" required aria-required="true" />
                            <p><?php esc_html_e('O telefone deve ser único.', 'agendamentos-wp'); ?></p>
                        </div>
                        <div class="form-field">
                            <label for="email_cliente"><?php esc_html_e( 'Email (Opcional)', 'agendamentos-wp' ); ?></label>
                            <input type="email" name="email_cliente" id="email_cliente" value="" />
                            <p><?php esc_html_e('Fornecer um email permite criar uma conta WordPress para o cliente.', 'agendamentos-wp'); ?></p>
                        </div>

                        <?php submit_button( __( 'Adicionar Cliente', 'agendamentos-wp' ), 'primary', 'submit_cliente' ); ?>
                    </form>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <h2><?php esc_html_e( 'Clientes Cadastrados', 'agendamentos-wp' ); ?></h2>
                    <?php
                    global $wpdb;
                    $table_name_clientes = $wpdb->prefix . 'clientes';
                    $clientes = $wpdb->get_results( "SELECT * FROM $table_name_clientes ORDER BY nome_cliente ASC" );
                    ?>
                    <div class="clientes-list-table-wrapper">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col"><?php esc_html_e( 'Nome do Cliente', 'agendamentos-wp' ); ?></th>
                                    <th scope="col"><?php esc_html_e( 'Telefone', 'agendamentos-wp' ); ?></th>
                                    <th scope="col"><?php esc_html_e( 'Valor Gasto', 'agendamentos-wp' ); ?></th>
                                    <!-- <th scope="col"><?php //esc_html_e( 'Ações', 'agendamentos-wp' ); ?></th> -->
                                </tr>
                            </thead>
                            <tbody id="the-list-clientes">
                                <?php if ( $clientes ) : ?>
                                    <?php foreach ( $clientes as $cliente ) : ?>
                                        <tr>
                                            <td class="column-primary">
                                                <strong><?php echo esc_html( $cliente->nome_cliente ); ?></strong>
                                                <!-- <div class="row-actions">
                                                    <span class="edit"><a href="#">Editar</a> | </span>
                                                    <span class="delete"><a href="#">Excluir</a></span>
                                                </div> -->
                                            </td>
                                            <td><?php echo esc_html( $cliente->telefone ); ?></td>
                                            <td>R$ <?php echo esc_html( number_format_i18n( $cliente->valor_gasto, 2 ) ); ?></td>
                                            <!-- <td>Ações aqui</td> -->
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="3"><?php esc_html_e( 'Nenhum cliente encontrado.', 'agendamentos-wp' ); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="clientes-list-mobile" style="display:none;">
                        <ul>
                        <?php if ( $clientes ) : ?>
                            <?php foreach ( $clientes as $cliente ) : ?>
                                <li>
                                    <strong><?php esc_html_e( 'Nome:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $cliente->nome_cliente ); ?><br>
                                    <strong><?php esc_html_e( 'Telefone:', 'agendamentos-wp' ); ?></strong> <?php echo esc_html( $cliente->telefone ); ?><br>
                                    <strong><?php esc_html_e( 'Valor Gasto:', 'agendamentos-wp' ); ?></strong> R$ <?php echo esc_html( number_format_i18n( $cliente->valor_gasto, 2 ) ); ?><br>
                                    <!-- Ações aqui -->
                                </li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <li><?php esc_html_e( 'Nenhum cliente encontrado.', 'agendamentos-wp' ); ?></li>
                        <?php endif; ?>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <?php
}


/**
 * Lida com o salvamento dos dados do formulário de adicionar/editar serviço.
 */
function agendamentos_wp_handle_save_servico() {
    // 1. Validação e Segurança
    if ( ! isset( $_POST['agendamentos_wp_save_servico_nonce'] ) || ! wp_verify_nonce( $_POST['agendamentos_wp_save_servico_nonce'], 'agendamentos_wp_save_servico_action' ) ) {
        wp_die( __( 'Falha na verificação de segurança (nonce).', 'agendamentos-wp' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Você não tem permissão para executar esta ação.', 'agendamentos-wp' ) );
    }

    $servico_id_update = isset( $_POST['servico_id_update'] ) ? intval( $_POST['servico_id_update'] ) : 0;
    $is_update = $servico_id_update > 0;

    $redirect_url = $is_update ? admin_url( 'admin.php?page=agendamentos-wp-all-servicos' ) : admin_url( 'admin.php?page=agendamentos-wp-add-servico' );
    $error_redirect_url = $is_update ? admin_url( 'admin.php?page=agendamentos-wp-add-servico&action=edit&servico_id=' . $servico_id_update ) : admin_url( 'admin.php?page=agendamentos-wp-add-servico' );


    // 2. Sanitizar e validar dados do POST
    $nome_servico = isset( $_POST['nome_servico'] ) ? sanitize_text_field( $_POST['nome_servico'] ) : '';
    $descricao_servico = isset( $_POST['descricao_servico'] ) ? sanitize_textarea_field( $_POST['descricao_servico'] ) : '';
    $duracao_servico = isset( $_POST['duracao_servico'] ) ? intval( $_POST['duracao_servico'] ) : 0;
    $preco_servico = isset( $_POST['preco_servico'] ) ? floatval( str_replace(',', '.', $_POST['preco_servico'] ) ) : 0; // Substitui vírgula por ponto para floatval
    $profissionais_selecionados = isset( $_POST['profissionais_servico'] ) && is_array( $_POST['profissionais_servico'] ) ? array_map( 'intval', $_POST['profissionais_servico'] ) : array();
    $exigir_sinal = isset( $_POST['exigir_sinal_servico'] ) ? 1 : 0;
    $porcentagem_sinal = isset( $_POST['porcentagem_sinal_servico'] ) ? intval( $_POST['porcentagem_sinal_servico'] ) : 0;

    // Validações básicas
    if ( empty( $nome_servico ) || $duracao_servico <= 0 || $preco_servico < 0 ) {
        $error_redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Erro: Nome do serviço, duração (maior que 0) e preço (maior ou igual a 0) são obrigatórios.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'error',
        ), $error_redirect_url );
        wp_redirect( $error_redirect_url );
        exit;
    }

    if ( $exigir_sinal && ( $porcentagem_sinal <= 0 || $porcentagem_sinal > 100 ) ) {
        $error_redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Erro: Se exigir sinal, a porcentagem deve ser entre 1 e 100.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'error',
        ), $error_redirect_url );
        wp_redirect( $error_redirect_url );
        exit;
    }
    if (!$exigir_sinal) {
        $porcentagem_sinal = null; // Salvar como NULL se não exigir sinal
    }

    global $wpdb;
    $table_name_servicos = $wpdb->prefix . 'servicos';
    $table_name_servicos_profissionais = $wpdb->prefix . 'servicos_profissionais';

    $data_servico = array(
        'nome_servico'    => $nome_servico,
        'descricao'       => $descricao_servico,
        'duracao'         => $duracao_servico,
        'preco'           => $preco_servico,
        'exigir_sinal'    => $exigir_sinal,
        'porcentagem_sinal' => $porcentagem_sinal,
    );
    $format_servico = array( '%s', '%s', '%d', '%f', '%d' ); // Formato base
    if (is_null($porcentagem_sinal)) {
        $format_servico[] = null; // Para porcentagem_sinal NULL
    } else {
        $format_servico[] = '%d'; // Para porcentagem_sinal INT
    }


    if ( $is_update ) {
        $result = $wpdb->update(
            $table_name_servicos,
            $data_servico,
            array( 'id' => $servico_id_update ),
            $format_servico,
            array( '%d' )
        );
        $current_servico_id = $servico_id_update; // Usar o ID existente para associações
        $message_type = 'success'; // Assume sucesso, erro será tratado abaixo
        $message = __( 'Serviço atualizado com sucesso!', 'agendamentos-wp' );

        if ($result === false) {
            $error_redirect_url = add_query_arg( array(
                'agendamentos_wp_message' => __( 'Erro ao atualizar serviço: ', 'agendamentos-wp' ) . $wpdb->last_error,
                'agendamentos_wp_message_type' => 'error',
            ), $error_redirect_url );
            wp_redirect( $error_redirect_url );
            exit;
        }

        // Atualizar associações: deletar antigas e inserir novas
        $wpdb->delete( $table_name_servicos_profissionais, array( 'servico_id' => $current_servico_id ), array( '%d' ) );

    } else {
        // CRIAR NOVO SERVIÇO
        $result = $wpdb->insert( $table_name_servicos, $data_servico, $format_servico );
        $current_servico_id = $wpdb->insert_id;
        $message_type = 'success';
        $message = __( 'Serviço adicionado com sucesso!', 'agendamentos-wp' );

        if ( $result === false || $current_servico_id === 0 ) {
             $error_redirect_url = add_query_arg( array(
                'agendamentos_wp_message' => __( 'Erro ao salvar serviço no banco de dados: ', 'agendamentos-wp' ) . $wpdb->last_error,
                'agendamentos_wp_message_type' => 'error',
            ), $error_redirect_url );
            wp_redirect( $error_redirect_url );
            exit;
        }
    }

    // Salvar/Re-salvar associações com profissionais
    if ( ! empty( $profissionais_selecionados ) ) {
        foreach ( $profissionais_selecionados as $profissional_id ) {
            if ( $profissional_id > 0 ) { // Certificar-se de que o ID é válido
                $wpdb->insert(
                    $table_name_servicos_profissionais,
                    array(
                        'servico_id' => $new_servico_id,
                        'profissional_id' => $profissional_id,
                    ),
                    array( '%d', '%d' )
                );
                // TODO: Adicionar tratamento de erro para esta inserção, se necessário
            }
        }
    }

    // Redirecionamento e Feedback
    // Se for criação e bem-sucedida, redirecionar para adicionar novo de novo (ou para lista)
    // Se for atualização e bem-sucedida, redirecionar para a lista
    if (!$is_update && $result) {
         $redirect_url = admin_url( 'admin.php?page=agendamentos-wp-add-servico' ); // Fica na mesma página
    }
    // else if ($is_update && $result) {
    //    $redirect_url = admin_url( 'admin.php?page=agendamentos-wp-all-servicos&message=' . $message_type );
    // }


    $redirect_url = add_query_arg( array(
        'agendamentos_wp_message' => $message,
        'agendamentos_wp_message_type' => $message_type,
    ), $redirect_url );
    wp_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_agendamentos_wp_save_servico', 'agendamentos_wp_handle_save_servico' );

/**
 * Lida com a exclusão de um serviço.
 */
function agendamentos_wp_handle_delete_servico() {
    $servico_id = isset( $_GET['servico_id'] ) ? intval( $_GET['servico_id'] ) : 0;
    $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';

    // 1. Validação e Segurança
    if ( ! wp_verify_nonce( $nonce, 'agendamentos_wp_delete_servico_nonce_' . $servico_id ) ) {
        wp_die( __( 'Falha na verificação de segurança (nonce) para exclusão de serviço.', 'agendamentos-wp' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Você não tem permissão para excluir serviços.', 'agendamentos-wp' ) );
    }

    $redirect_url = admin_url( 'admin.php?page=agendamentos-wp-all-servicos' );

    if ( $servico_id <= 0 ) {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'ID do serviço inválido para exclusão.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }

    global $wpdb;
    $table_name_servicos = $wpdb->prefix . 'servicos';
    $table_name_servicos_profissionais = $wpdb->prefix . 'servicos_profissionais';

    // Excluir associações da tabela 'servicos_profissionais'
    $wpdb->delete( $table_name_servicos_profissionais, array( 'servico_id' => $servico_id ), array( '%d' ) );
    // Não é crítico verificar o resultado aqui, pois mesmo que não haja associações, o serviço deve ser excluído.

    // Excluir da tabela 'servicos'
    $deleted_rows_servicos = $wpdb->delete( $table_name_servicos, array( 'id' => $servico_id ), array( '%d' ) );

    if ( $deleted_rows_servicos === false ) {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Erro ao excluir serviço da tabela principal: ', 'agendamentos-wp' ) . $wpdb->last_error,
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
    } elseif ( $deleted_rows_servicos == 0 ) {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Nenhum serviço encontrado com este ID para exclusão.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'warning',
        ), $redirect_url );
    } else {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Serviço excluído com sucesso!', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'success',
        ), $redirect_url );
    }

    wp_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_agendamentos_wp_delete_servico', 'agendamentos_wp_handle_delete_servico' );


// ########## API REST ##########

/**
 * Registra as rotas da API REST para o plugin Agendamentos WP.
 */
function agendamentos_wp_register_api_routes() {
    $namespace = 'agendamentos/v1';

    // Endpoint: GET /servicos (Listar todos os serviços)
    register_rest_route( $namespace, '/servicos', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'agendamentos_wp_api_get_servicos',
        'permission_callback' => '__return_true', // Público por enquanto
    ) );

    // Endpoint: POST /clientes (Criar novo cliente)
    register_rest_route( $namespace, '/clientes', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST
        'callback'            => 'agendamentos_wp_api_create_cliente',
        'permission_callback' => '__return_true', // Permitir criação anônima por enquanto
        'args'                => array(
            'nome_cliente' => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'Nome completo do cliente.', 'agendamentos-wp' ),
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'telefone' => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'Número de telefone do cliente. Deve ser único.', 'agendamentos-wp' ),
                'sanitize_callback' => 'sanitize_text_field',
                // 'validate_callback' => 'agendamentos_wp_validate_telefone_param', // Implementar se necessário
            ),
            'email' => array(
                'required'          => false,
                'type'              => 'string',
                'description'       => __( 'Endereço de e-mail do cliente (opcional).', 'agendamentos-wp' ),
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email', // Validação nativa do WP
            ),
        ),
    ) );

    // Endpoint: GET /clientes/buscar (Buscar cliente por telefone)
    register_rest_route( $namespace, '/clientes/buscar', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'agendamentos_wp_api_find_cliente_by_phone',
        'permission_callback' => '__return_true', // Público por enquanto
        'args'                => array(
            'telefone' => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __( 'Número de telefone do cliente para busca.', 'agendamentos-wp' ),
                'sanitize_callback' => 'sanitize_text_field',
                // Adicionar 'validate_callback' para formato de telefone, se necessário
            ),
        ),
    ) );

    // Endpoint: POST /agendamentos (Criar novo agendamento)
    register_rest_route( $namespace, '/agendamentos', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST
        'callback'            => 'agendamentos_wp_api_create_agendamento',
        'permission_callback' => '__return_true', // Por enquanto
        'args'                => array(
            'servico_id' => array(
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'agendamentos_wp_validate_positive_integer',
            ),
            'profissional_id' => array(
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'agendamentos_wp_validate_positive_integer',
            ),
            'cliente_id' => array(
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'agendamentos_wp_validate_positive_integer',
            ),
            'data_agendamento' => array(
                'required'          => true,
                'type'              => 'string',
                'validate_callback' => 'agendamentos_wp_validate_date_format', // YYYY-MM-DD
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'hora_agendamento' => array(
                'required'          => true,
                'type'              => 'string',
                'validate_callback' => 'agendamentos_wp_validate_time_format', // HH:MM (formato 24h)
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );

    // Endpoint: GET /agendamentos/data/{data} (Listar agendamentos de uma data)
    register_rest_route( $namespace, '/agendamentos/data/(?P<data>\d{4}-\d{2}-\d{2})', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agendamentos_wp_api_get_agendamentos_by_date',
        'permission_callback' => '__return_true',
        'args'                => array(
            'data' => array(
                'validate_callback' => 'agendamentos_wp_validate_date_format_from_route', // Valida o formato da data da URL
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));

    // Endpoint: GET /disponibilidade (Verificar horários disponíveis)
    register_rest_route( $namespace, '/disponibilidade', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agendamentos_wp_api_get_disponibilidade',
        'permission_callback' => '__return_true',
        'args'                => array(
            'profissional_id' => array(
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'agendamentos_wp_validate_positive_integer',
            ),
            'servico_id' => array(
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'agendamentos_wp_validate_positive_integer',
            ),
            'data' => array( // Nome do parâmetro alterado para 'data' para consistência
                'required'          => true,
                'type'              => 'string',
                'validate_callback' => 'agendamentos_wp_validate_date_format', // Reutiliza a validação YYYY-MM-DD
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
}
add_action( 'rest_api_init', 'agendamentos_wp_register_api_routes' );

/**
 * Valida se um parâmetro é um inteiro positivo.
 */
function agendamentos_wp_validate_positive_integer( $param, $request, $key ) {
    return is_numeric( $param ) && absint( $param ) > 0;
}

/**
 * Valida o formato de data (YYYY-MM-DD).
 */
function agendamentos_wp_validate_date_format( $param, $request, $key ) {
    return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $param ) === 1;
}

/**
 * Valida o formato de data (YYYY-MM-DD) vindo da rota (parâmetro da URL).
 */
function agendamentos_wp_validate_date_format_from_route( $param, $request, $key ) {
    // O parâmetro 'data' já foi validado pela regex da rota,
    // mas uma verificação adicional pode ser feita aqui se necessário.
    // Ex: verificar se a data é válida (ex: não é 2023-02-30) usando checkdate()
    list($year, $month, $day) = explode('-', $param);
    if (!checkdate((int)$month, (int)$day, (int)$year)) {
        return new WP_Error('rest_invalid_param', __('Data inválida.', 'agendamentos-wp'), array('status' => 400));
    }
    return true;
}

/**
 * Valida o formato de hora (HH:MM).
 */
function agendamentos_wp_validate_time_format( $param, $request, $key ) {
    return preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $param ) === 1;
}

/**
 * Retorna uma lista de horários disponíveis para um profissional, serviço e data.
 *
 * @param int $profissional_id
 * @param int $servico_id
 * @param string $data_consulta YYYY-MM-DD
 * @return array|WP_Error Lista de strings HH:MM ou WP_Error.
 */
function agendamentos_wp_get_available_slots_for_day($profissional_id, $servico_id, $data_consulta) {
    global $wpdb;
    $slots_disponiveis = array();

    // Buscar detalhes do serviço (duração)
    $servico_table = $wpdb->prefix . 'servicos';
    $servico = $wpdb->get_row($wpdb->prepare("SELECT duracao FROM $servico_table WHERE id = %d", $servico_id));
    if (!$servico) {
        return new WP_Error('servico_invalido_slots', __('Serviço não encontrado para calcular slots.', 'agendamentos-wp'), array('status' => 404));
    }
    $duracao_servico_minutos = (int) $servico->duracao;
    if ($duracao_servico_minutos <=0) {
         return new WP_Error('duracao_invalida_slots', __('Duração do serviço inválida.', 'agendamentos-wp'), array('status' => 400));
    }

    // Determinar dia da semana
    try {
        $data_obj = new DateTime($data_consulta);
        $dia_semana = (int) $data_obj->format('w');
    } catch (Exception $e) {
        return new WP_Error('data_invalida_slots', __('Formato de data inválido para calcular slots.', 'agendamentos-wp'), array('status' => 400));
    }

    // Buscar horário de trabalho do profissional
    $horarios_table = $wpdb->prefix . 'horarios';
    $horario_profissional = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $horarios_table WHERE profissional_id = %d AND dia_semana = %d",
        $profissional_id, $dia_semana
    ));

    if (!$horario_profissional || $horario_profissional->dia_folga || !$horario_profissional->horario_inicio || !$horario_profissional->horario_termino) {
        return array(); // Dia de folga, sem horário configurado ou horários inválidos, retorna array vazio de slots
    }

    try {
        $inicio_expediente_dt = new DateTime($data_consulta . ' ' . $horario_profissional->horario_inicio);
        $fim_expediente_dt = new DateTime($data_consulta . ' ' . $horario_profissional->horario_termino);
    } catch (Exception $e) {
         return new WP_Error('horario_expediente_invalido', __('Horário de expediente do profissional é inválido.', 'agendamentos-wp'), array('status' => 500));
    }

    $intervalo_slot_minutos = 15; // Define a granularidade da verificação (ex: a cada 15 minutos)
    $slot_atual_dt = clone $inicio_expediente_dt;

    while ($slot_atual_dt < $fim_expediente_dt) {
        $hora_formatada = $slot_atual_dt->format('H:i');

        // Verifica se o slot atual + duração do serviço ultrapassa o fim do expediente
        $slot_termino_potencial_dt = clone $slot_atual_dt;
        $slot_termino_potencial_dt->add(new DateInterval('PT' . $duracao_servico_minutos . 'M'));
        if ($slot_termino_potencial_dt > $fim_expediente_dt) {
            break; // Não há mais slots possíveis que caibam no expediente
        }

        $disponibilidade_check = agendamentos_wp_check_availability($profissional_id, $servico_id, $data_consulta, $hora_formatada);

        if ($disponibilidade_check === true) {
            $slots_disponiveis[] = $hora_formatada;
        } elseif (is_wp_error($disponibilidade_check) && $disponibilidade_check->get_error_code() === 'fora_expediente') {
            // Se agendamentos_wp_check_availability retornar 'fora_expediente' para um slot que achamos que está dentro,
            // pode ser devido à duração do serviço empurrando para fora. Nesse caso, paramos.
            break;
        }

        // Avança para o próximo slot potencial
        $slot_atual_dt->add(new DateInterval('PT' . $intervalo_slot_minutos . 'M'));
    }

    return $slots_disponiveis;
}


/**
 * Callback para o endpoint GET /disponibilidade.
 */
function agendamentos_wp_api_get_disponibilidade( WP_REST_Request $request ) {
    $profissional_id = $request->get_param('profissional_id');
    $servico_id = $request->get_param('servico_id');
    $data_consulta = $request->get_param('data');

    // Validações básicas dos IDs (já feitas pelo 'validate_callback' da rota, mas uma checagem extra não prejudica)
    if (empty($profissional_id) || empty($servico_id) || empty($data_consulta)) {
        return new WP_Error('parametros_faltando', __('profissional_id, servico_id e data são obrigatórios.', 'agendamentos-wp'), array('status' => 400));
    }

    // Validação de data (já feita pelo 'validate_callback' da rota)

    $slots = agendamentos_wp_get_available_slots_for_day($profissional_id, $servico_id, $data_consulta);

    if (is_wp_error($slots)) {
        return $slots; // Propaga o WP_Error
    }

    return new WP_REST_Response($slots, 200);
}


/**
 * Verifica a disponibilidade de um horário para agendamento.
 * (Esqueleto - lógica detalhada precisa ser implementada e testada cuidadosamente)
 *
 * @param int $profissional_id ID do profissional.
 * @param int $servico_id ID do serviço.
 * @param string $data_agendamento Data no formato YYYY-MM-DD.
 * @param string $hora_agendamento Hora no formato HH:MM.
 * @return bool|WP_Error True se disponível, WP_Error com detalhes se indisponível ou erro.
 */
function agendamentos_wp_check_availability($profissional_id, $servico_id, $data_agendamento, $hora_agendamento) {
    global $wpdb;

    // 1. Buscar detalhes do serviço (duração)
    $servico_table = $wpdb->prefix . 'servicos';
    $servico = $wpdb->get_row($wpdb->prepare("SELECT duracao FROM $servico_table WHERE id = %d", $servico_id));
    if (!$servico) {
        return new WP_Error('servico_invalido', __('Serviço não encontrado.', 'agendamentos-wp'), array('status' => 404));
    }
    $duracao_servico_minutos = (int) $servico->duracao;

    // 2. Determinar dia da semana (0=Domingo, ..., 6=Sábado)
    try {
        $data_obj = new DateTime($data_agendamento);
        $dia_semana = (int) $data_obj->format('w');
    } catch (Exception $e) {
        return new WP_Error('data_invalida', __('Formato de data inválido.', 'agendamentos-wp'), array('status' => 400));
    }


    // 3. Buscar horário de trabalho do profissional para o dia da semana
    $horarios_table = $wpdb->prefix . 'horarios';
    $horario_profissional = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $horarios_table WHERE profissional_id = %d AND dia_semana = %d",
        $profissional_id, $dia_semana
    ));

    if (!$horario_profissional || $horario_profissional->dia_folga) {
        return new WP_Error('dia_indisponivel', __('Profissional não trabalha ou está de folga neste dia.', 'agendamentos-wp'), array('status' => 409));
    }

    // Converter todos os horários para objetos DateTime para facilitar comparações
    try {
        $inicio_expediente = new DateTime($data_agendamento . ' ' . $horario_profissional->horario_inicio);
        $fim_expediente = new DateTime($data_agendamento . ' ' . $horario_profissional->horario_termino);
        $inicio_almoco = $horario_profissional->inicio_almoco ? new DateTime($data_agendamento . ' ' . $horario_profissional->inicio_almoco) : null;
        $fim_almoco = $horario_profissional->termino_almoco ? new DateTime($data_agendamento . ' ' . $horario_profissional->termino_almoco) : null;

        $hora_agendamento_dt = new DateTime($data_agendamento . ' ' . $hora_agendamento);
        $hora_termino_servico_dt = clone $hora_agendamento_dt;
        $hora_termino_servico_dt->add(new DateInterval('PT' . $duracao_servico_minutos . 'M'));
    } catch (Exception $e) {
        return new WP_Error('hora_invalida', __('Formato de hora inválido para cálculo.', 'agendamentos-wp'), array('status' => 400));
    }

    $pausa_entre_atendimentos_minutos = (int) $horario_profissional->pausa_entre_atendimentos;


    // 4. Verificar se o horário solicitado está dentro do expediente
    if ($hora_agendamento_dt < $inicio_expediente || $hora_termino_servico_dt > $fim_expediente) {
         return new WP_Error('fora_expediente', __('Horário solicitado fora do expediente do profissional.', 'agendamentos-wp'), array('status' => 409));
    }

    // 5. Verificar se colide com o horário de almoço
    if ($inicio_almoco && $fim_almoco) {
        if (($hora_agendamento_dt >= $inicio_almoco && $hora_agendamento_dt < $fim_almoco) || // Começa durante o almoço
            ($hora_termino_servico_dt > $inicio_almoco && $hora_termino_servico_dt <= $fim_almoco) || // Termina durante o almoço
            ($hora_agendamento_dt < $inicio_almoco && $hora_termino_servico_dt > $fim_almoco)) { // Cobre o almoço
            return new WP_Error('conflito_almoco', __('Horário solicitado coincide com o almoço do profissional.', 'agendamentos-wp'), array('status' => 409));
        }
    }

    // 6. Verificar colisões com outros agendamentos do profissional na mesma data
    $agendamentos_table = $wpdb->prefix . 'agendamentos';
    $servicos_table_for_duration = $wpdb->prefix . 'servicos'; // Renomeada para evitar conflito de alias 's'

    $agendamentos_existentes = $wpdb->get_results($wpdb->prepare(
        "SELECT a.hora_agendamento, s_dur.duracao
         FROM {$agendamentos_table} a
         JOIN {$servicos_table_for_duration} s_dur ON a.servico_id = s_dur.id
         WHERE a.profissional_id = %d AND a.data_agendamento = %s AND a.status NOT IN ('cancelado', 'recusado')", // Adicionado 'recusado' se existir
        $profissional_id, $data_agendamento
    ));

    // Intervalo do novo agendamento com pausas
    // Não há necessidade de $novo_inicio_com_pausa e $novo_fim_com_pausa aqui,
    // pois a pausa é considerada *entre* agendamentos.

    foreach ($agendamentos_existentes as $ag) {
        try {
            $existente_inicio_dt = new DateTime($data_agendamento . ' ' . $ag->hora_agendamento);
            $existente_fim_dt = clone $existente_inicio_dt;
            $existente_fim_dt->add(new DateInterval('PT' . (int)$ag->duracao . 'M'));
        } catch (Exception $e) {
            error_log("Erro ao processar data/hora de agendamento existente para checagem de disponibilidade: " . $e->getMessage());
            continue; // Pula este agendamento problemático
        }

        // Horário proposto: $hora_agendamento_dt até $hora_termino_servico_dt
        // Horário existente: $existente_inicio_dt até $existente_fim_dt

        // Adicionar pausa ao final do agendamento existente
        $existente_fim_com_pausa = clone $existente_fim_dt;
        $existente_fim_com_pausa->add(new DateInterval('PT' . $pausa_entre_atendimentos_minutos . 'M'));

        // Adicionar pausa ao início do agendamento existente (para checar o slot ANTES dele)
        $existente_inicio_com_pausa = clone $existente_inicio_dt;
        $existente_inicio_com_pausa->sub(new DateInterval('PT' . $pausa_entre_atendimentos_minutos . 'M'));

        // O novo agendamento não pode começar antes que um existente + sua pausa termine.
        // E o novo agendamento + sua pausa não pode terminar depois que um existente comece.
        // Simplificando: Novo slot [A, B]. Existente slot [X, Y]. Pausa P.
        // Bloqueio do existente é [X-P, Y+P] para o novo.
        // Ou seja, o [A,B] não pode sobrepor [X-P, Y+P].
        // Teste de sobreposição: (StartA < EndB) and (EndA > StartB)

        // Intervalo bloqueado pelo agendamento existente, incluindo pausas em ambos os lados.
        // Início do bloqueio = início do agendamento existente - pausa.
        // Fim do bloqueio = fim do agendamento existente + pausa.

        // Se o novo agendamento começa ($hora_agendamento_dt) antes do (fim do existente + pausa)
        // E o novo agendamento termina ($hora_termino_servico_dt) depois do (início do existente - pausa)
        // Então há um conflito.
        if ($hora_agendamento_dt < $existente_fim_com_pausa && $hora_termino_servico_dt > $existente_inicio_com_pausa) {
             return new WP_Error(
                'horario_conflito_com_pausa',
                __('O horário solicitado conflita com um agendamento existente ou sua pausa de atendimento.', 'agendamentos-wp'),
                array('status' => 409)
            );
        }
    }
    return true;
}


/**
 * Callback para o endpoint POST /agendamentos.
 * Cria um novo agendamento.
 */
function agendamentos_wp_api_create_agendamento( WP_REST_Request $request ) {
    global $wpdb;
    $params = $request->get_params();

    $servico_id = $params['servico_id'];
    $profissional_id = $params['profissional_id'];
    $cliente_id = $params['cliente_id'];
    $data_agendamento = $params['data_agendamento'];
    $hora_agendamento = $params['hora_agendamento'];

    // Validações Iniciais de Existência
    $servico_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}servicos WHERE id = %d", $servico_id));
    if (!$servico_exists) {
        return new WP_Error('entidade_nao_encontrada', __('Serviço não encontrado.', 'agendamentos-wp'), array('status' => 404));
    }
    $profissional_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}profissionais WHERE id = %d", $profissional_id));
    if (!$profissional_exists) {
        return new WP_Error('entidade_nao_encontrada', __('Profissional não encontrado.', 'agendamentos-wp'), array('status' => 404));
    }
    $cliente_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}clientes WHERE id = %d", $cliente_id));
    if (!$cliente_exists) {
        return new WP_Error('entidade_nao_encontrada', __('Cliente não encontrado.', 'agendamentos-wp'), array('status' => 404));
    }

    // Verificar se o profissional realiza o serviço
    $profissional_realiza_servico = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}servicos_profissionais WHERE servico_id = %d AND profissional_id = %d",
        $servico_id, $profissional_id
    ));
    if (!$profissional_realiza_servico) {
        return new WP_Error('servico_profissional_incompativel', __('O profissional selecionado não realiza este serviço.', 'agendamentos-wp'), array('status' => 400));
    }

    // Calcular preço total (buscar preço do serviço)
    $preco_servico = $wpdb->get_var($wpdb->prepare("SELECT preco FROM {$wpdb->prefix}servicos WHERE id = %d", $servico_id));
    if (is_null($preco_servico)) { // Checagem adicional caso o serviço tenha sido removido entre as verificações
         return new WP_Error('servico_preco_invalido', __('Preço do serviço não encontrado.', 'agendamentos-wp'), array('status' => 500));
    }

    // Verificar disponibilidade
    $disponibilidade = agendamentos_wp_check_availability($profissional_id, $servico_id, $data_agendamento, $hora_agendamento);
    if (is_wp_error($disponibilidade)) {
        return $disponibilidade; // Retorna o WP_Error da função de disponibilidade
    }
    if ($disponibilidade !== true) { // Segurança adicional, embora a função deva retornar WP_Error
        return new WP_Error('erro_disponibilidade_desconhecido', __('Erro ao verificar disponibilidade.', 'agendamentos-wp'), array('status' => 500));
    }

    // Salvar Agendamento
    $table_name_agendamentos = $wpdb->prefix . 'agendamentos';
    $data_insert_agendamento = array(
        'servico_id'       => $servico_id,
        'profissional_id'  => $profissional_id,
        'cliente_id'       => $cliente_id,
        'data_agendamento' => $data_agendamento,
        'hora_agendamento' => $hora_agendamento,
        'total'            => (float) $preco_servico,
        'status'           => 'pendente', // Padrão
    );
    $format_insert_agendamento = array('%d', '%d', '%d', '%s', '%s', '%f', '%s');

    $result_insert = $wpdb->insert($table_name_agendamentos, $data_insert_agendamento, $format_insert_agendamento);

    if ($result_insert === false) {
        return new WP_Error('db_insert_error', __('Erro ao salvar o agendamento no banco de dados.', 'agendamentos-wp'), array('status' => 500));
    }

    return new WP_REST_Response(array(
        'message' => __('Agendamento criado com sucesso!', 'agendamentos-wp'),
        'agendamento_id' => $wpdb->insert_id
    ), 201);
}


/**
 * Callback para o endpoint GET /agendamentos/data/{data}.
 * Retorna os agendamentos para uma data específica.
 */
function agendamentos_wp_api_get_agendamentos_by_date( WP_REST_Request $request ) {
    global $wpdb;
    $data = $request->get_param('data');

    // A validação do formato da data já é feita pela regex da rota e pelo 'validate_callback'

    $query = $wpdb->prepare("
        SELECT
            a.id,
            s.nome_servico,
            p.nome_profissional,
            c.nome_cliente,
            c.telefone AS cliente_telefone,
            a.data_agendamento,
            a.hora_agendamento,
            a.total,
            a.status
        FROM
            {$wpdb->prefix}agendamentos a
        LEFT JOIN
            {$wpdb->prefix}servicos s ON a.servico_id = s.id
        LEFT JOIN
            {$wpdb->prefix}profissionais p ON a.profissional_id = p.id
        LEFT JOIN
            {$wpdb->prefix}clientes c ON a.cliente_id = c.id
        WHERE a.data_agendamento = %s
        ORDER BY a.hora_agendamento ASC
    ", $data);

    $agendamentos = $wpdb->get_results( $query );

    if (empty($agendamentos)) {
        return new WP_REST_Response(array(), 200); // Retorna array vazio se não houver agendamentos
    }

    // Formatar dados para a resposta, se necessário (ex: converter tipos)
    $agendamentos_formatados = array_map(function($ag) {
        $ag->id = (int) $ag->id;
        $ag->total = (float) $ag->total;
        // Formatar hora para HH:MM
        $ag->hora_agendamento = substr($ag->hora_agendamento, 0, 5);
        return $ag;
    }, $agendamentos);

    return new WP_REST_Response( $agendamentos_formatados, 200 );
}


/**
 * Callback para o endpoint GET /servicos.
 * Retorna a lista de todos os serviços.
 *
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response|WP_Error
 */
function agendamentos_wp_api_get_servicos( WP_REST_Request $request ) {
    global $wpdb;
    $table_servicos = $wpdb->prefix . 'servicos';
    $table_profissionais = $wpdb->prefix . 'profissionais';
    $table_servicos_profissionais = $wpdb->prefix . 'servicos_profissionais';

    // Query para buscar serviços e os IDs e nomes dos profissionais associados
    $query = "
        SELECT
            s.*,
            GROUP_CONCAT(DISTINCT p.id SEPARATOR ',') as profissional_ids,
            GROUP_CONCAT(DISTINCT p.nome_profissional SEPARATOR ', ') as profissional_nomes
        FROM {$table_servicos} s
        LEFT JOIN {$table_servicos_profissionais} sp ON s.id = sp.servico_id
        LEFT JOIN {$table_profissionais} p ON sp.profissional_id = p.id
        GROUP BY s.id
        ORDER BY s.nome_servico ASC
    ";

    $servicos_results = $wpdb->get_results( $query );

    if ( empty( $servicos_results ) ) {
        return new WP_REST_Response( array(), 200 ); // Retorna array vazio se não houver serviços
    }

    $servicos_formatados = array();
    foreach ( $servicos_results as $servico ) {
        $profissionais_data = array();
        if ( !empty($servico->profissional_ids) && !empty($servico->profissional_nomes) ) {
            $ids = explode(',', $servico->profissional_ids);
            $nomes = explode(', ', $servico->profissional_nomes); // Cuidado se nomes tiverem vírgula
             // Para uma correspondência mais robusta, seria melhor buscar os profissionais em uma query separada por ID
             // ou formatar a saída do GROUP_CONCAT como JSON.
             // Por simplicidade, vamos assumir que a ordem e a contagem correspondem.
            for ($i = 0; $i < count($ids); $i++) {
                if(isset($ids[$i]) && isset($nomes[$i])) { // Checa se ambos existem
                    $profissionais_data[] = array(
                        'id' => (int) $ids[$i],
                        'nome_profissional' => trim($nomes[$i]),
                    );
                }
            }
        }

        $servicos_formatados[] = array(
            'id'                => (int) $servico->id,
            'nome_servico'      => $servico->nome_servico,
            'descricao'         => $servico->descricao,
            'duracao'           => (int) $servico->duracao,
            'preco'             => (float) $servico->preco,
            'exigir_sinal'      => (bool) $servico->exigir_sinal,
            'porcentagem_sinal' => $servico->exigir_sinal ? (int) $servico->porcentagem_sinal : null,
            'profissionais'     => $profissionais_data,
        );
    }

    return new WP_REST_Response( $servicos_formatados, 200 );
}

/**
 * Callback para o endpoint POST /clientes.
 * Cria um novo cliente e um usuário WordPress associado.
 *
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response|WP_Error
 */
function agendamentos_wp_api_create_cliente( WP_REST_Request $request ) {
    global $wpdb;
    $params = $request->get_params();

    $nome_cliente = $params['nome_cliente'];
    $telefone_cliente = $params['telefone'];
    $email_cliente = isset( $params['email'] ) ? $params['email'] : '';

    // Validação de unicidade do telefone
    $table_name_clientes = $wpdb->prefix . 'clientes';
    $telefone_existente = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name_clientes WHERE telefone = %s", $telefone_cliente ) );
    if ( $telefone_existente ) {
        return new WP_Error(
            'cliente_telefone_exists',
            __( 'Este número de telefone já está cadastrado.', 'agendamentos-wp' ),
            array( 'status' => 400 ) // Bad Request
        );
    }

    // Criar usuário WordPress
    $user_creation_result = agendamentos_wp_create_cliente_user( $nome_cliente, $telefone_cliente, $email_cliente );
    if ( is_wp_error( $user_creation_result ) ) {
        // Se agendamentos_wp_create_cliente_user já retorna um WP_Error com status, ele será usado.
        // Se o erro for 'email_exists', o status 400 já está implícito.
        // Adicionar um status padrão se não vier do helper.
        if (empty($user_creation_result->get_error_data())) {
             return new WP_Error(
                $user_creation_result->get_error_code(),
                $user_creation_result->get_error_message(),
                array( 'status' => 400 )
            );
        }
        return $user_creation_result;
    }
    $user_id_wp = $user_creation_result->ID;

    // Salvar na tabela `clientes`
    $data_cliente_db = array(
        'nome_cliente' => $nome_cliente,
        'telefone'     => $telefone_cliente,
        'user_id'      => $user_id_wp,
        'valor_gasto'  => 0.00,
    );
    $format_cliente_db = array( '%s', '%s', '%d', '%f' );

    $result_insert_cliente = $wpdb->insert( $table_name_clientes, $data_cliente_db, $format_cliente_db );

    if ( $result_insert_cliente === false ) {
        // Rollback: deletar o usuário WP criado
        wp_delete_user( $user_id_wp );
        return new WP_Error(
            'cliente_db_error',
            __( 'Erro ao salvar cliente no banco de dados.', 'agendamentos-wp' ),
            array( 'status' => 500 ) // Internal Server Error
        );
    }

    $new_cliente_id = $wpdb->insert_id;

    $response_data = array(
        'message'    => __( 'Cliente criado com sucesso!', 'agendamentos-wp' ),
        'cliente_id' => $new_cliente_id,
        'user_id'    => $user_id_wp,
        'data'       => array(
            'nome_cliente' => $nome_cliente,
            'telefone'     => $telefone_cliente,
            'email'        => $email_cliente, // Retornar o email usado/sanitizado
            'wp_username'  => $user_creation_result->user_login,
        ),
    );

    return new WP_REST_Response( $response_data, 201 ); // 201 Created
}

/**
 * Callback para o endpoint GET /clientes/buscar.
 * Retorna os dados de um cliente com base no número de telefone.
 *
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response|WP_Error
 */
function agendamentos_wp_api_find_cliente_by_phone( WP_REST_Request $request ) {
    global $wpdb;
    $telefone = $request->get_param('telefone');

    // Sanitizar o telefone pode envolver remover caracteres não numéricos,
    // dependendo de como ele é armazenado e como se espera que seja enviado.
    // Por enquanto, sanitize_text_field é um bom começo.
    // $telefone_sanitizado = preg_replace('/\D/', '', $telefone); // Exemplo se quisesse apenas números

    $table_name_clientes = $wpdb->prefix . 'clientes';
    $cliente = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, nome_cliente, telefone, user_id, valor_gasto FROM $table_name_clientes WHERE telefone = %s",
        $telefone
    ) );

    if ( empty( $cliente ) ) {
        return new WP_Error(
            'cliente_not_found',
            __( 'Cliente não encontrado com o telefone fornecido.', 'agendamentos-wp' ),
            array( 'status' => 404 ) // Not Found
        );
    }

    // Converter tipos de dados para o formato correto na resposta JSON
    $cliente->id = (int) $cliente->id;
    $cliente->user_id = (int) $cliente->user_id;
    $cliente->valor_gasto = (float) $cliente->valor_gasto;


    return new WP_REST_Response( $cliente, 200 );
}


/**
 * Lida com o salvamento dos dados do formulário de horários.
 */
function agendamentos_wp_handle_save_horarios() {
    // 1. Validação e Segurança
    if ( ! isset( $_POST['agendamentos_wp_save_horarios_nonce'] ) || ! wp_verify_nonce( $_POST['agendamentos_wp_save_horarios_nonce'], 'agendamentos_wp_save_horarios_action' ) ) {
        wp_die( __( 'Falha na verificação de segurança (nonce).', 'agendamentos-wp' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Você não tem permissão para executar esta ação.', 'agendamentos-wp' ) );
    }

    $profissional_id = isset( $_POST['profissional_id'] ) ? intval( $_POST['profissional_id'] ) : 0;
    $horarios_data = isset( $_POST['horarios'] ) && is_array( $_POST['horarios'] ) ? $_POST['horarios'] : array();

    $redirect_url = admin_url( 'admin.php?page=agendamentos-wp-horarios' );
    if ( $profissional_id > 0 ) {
        $redirect_url = add_query_arg( 'profissional_id_horarios', $profissional_id, $redirect_url );
    }

    if ( $profissional_id <= 0 ) {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'ID do profissional inválido.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }

    if ( empty( $horarios_data ) ) {
        $redirect_url = add_query_arg( array(
            'agendamentos_wp_message' => __( 'Nenhum dado de horário recebido.', 'agendamentos-wp' ),
            'agendamentos_wp_message_type' => 'error',
        ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }

    global $wpdb;
    $table_name_horarios = $wpdb->prefix . 'horarios';
    $dias_semana_indices = array(0, 1, 2, 3, 4, 5, 6); // Domingo a Sábado

    foreach ( $dias_semana_indices as $dia_index ) {
        $dia_data = isset( $horarios_data[ $dia_index ] ) ? $horarios_data[ $dia_index ] : null;

        $dia_folga = isset( $dia_data['dia_folga'] ) ? 1 : 0;
        // Sanitizar e validar os campos de horário
        $horario_inicio = isset( $dia_data['horario_inicio'] ) && preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $dia_data['horario_inicio']) ? $dia_data['horario_inicio'] : null;
        $horario_termino = isset( $dia_data['horario_termino'] ) && preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $dia_data['horario_termino']) ? $dia_data['horario_termino'] : null;
        $inicio_almoco = isset( $dia_data['inicio_almoco'] ) && preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $dia_data['inicio_almoco']) ? $dia_data['inicio_almoco'] : null;
        $termino_almoco = isset( $dia_data['termino_almoco'] ) && preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $dia_data['termino_almoco']) ? $dia_data['termino_almoco'] : null;
        $pausa_entre_atendimentos = isset( $dia_data['pausa_entre_atendimentos'] ) ? intval( $dia_data['pausa_entre_atendimentos'] ) : null;

        if ($pausa_entre_atendimentos < 0) $pausa_entre_atendimentos = null;


        // Validações de lógica de horário (ex: término > início)
        if (!$dia_folga) {
            if ($horario_inicio && $horario_termino && strtotime($horario_termino) <= strtotime($horario_inicio)) {
                 $redirect_url = add_query_arg( array(
                    'agendamentos_wp_message' => sprintf(__( 'Erro no dia %d: Horário de término deve ser após o horário de início.', 'agendamentos-wp' ), $dia_index),
                    'agendamentos_wp_message_type' => 'error',
                ), $redirect_url );
                wp_redirect( $redirect_url );
                exit;
            }
            if ($inicio_almoco && $termino_almoco && strtotime($termino_almoco) <= strtotime($inicio_almoco)) {
                 $redirect_url = add_query_arg( array(
                    'agendamentos_wp_message' => sprintf(__( 'Erro no dia %d: Término do almoço deve ser após o início do almoço.', 'agendamentos-wp' ), $dia_index),
                    'agendamentos_wp_message_type' => 'error',
                ), $redirect_url );
                wp_redirect( $redirect_url );
                exit;
            }
            if ($horario_inicio && $horario_termino && $inicio_almoco && $termino_almoco) {
                if (strtotime($inicio_almoco) < strtotime($horario_inicio) || strtotime($termino_almoco) > strtotime($horario_termino)) {
                    $redirect_url = add_query_arg( array(
                        'agendamentos_wp_message' => sprintf(__( 'Erro no dia %d: Horário de almoço deve estar dentro do horário de trabalho.', 'agendamentos-wp' ), $dia_index),
                        'agendamentos_wp_message_type' => 'error',
                    ), $redirect_url );
                    wp_redirect( $redirect_url );
                    exit;
                }
            }
        }


        $dados_db = array(
            'profissional_id' => $profissional_id,
            'dia_semana' => $dia_index,
            'dia_folga' => $dia_folga,
            'horario_inicio' => $dia_folga ? null : $horario_inicio,
            'horario_termino' => $dia_folga ? null : $horario_termino,
            'inicio_almoco' => $dia_folga ? null : $inicio_almoco,
            'termino_almoco' => $dia_folga ? null : $termino_almoco,
            'pausa_entre_atendimentos' => $dia_folga ? null : $pausa_entre_atendimentos,
        );

        $formatos_db = array(
            '%d', // profissional_id
            '%d', // dia_semana
            '%d', // dia_folga
            '%s', // horario_inicio
            '%s', // horario_termino
            '%s', // inicio_almoco
            '%s', // termino_almoco
            '%d', // pausa_entre_atendimentos
        );

        // Verificar se já existe um registro para este profissional e dia
        $horario_existente_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table_name_horarios WHERE profissional_id = %d AND dia_semana = %d",
            $profissional_id,
            $dia_index
        ) );

        if ( $horario_existente_id ) {
            // Atualizar
            $wpdb->update( $table_name_horarios, $dados_db, array( 'id' => $horario_existente_id ), $formatos_db, array( '%d' ) );
        } else {
            // Inserir
            // Apenas insere se não for dia de folga OU se for dia de folga e algum outro campo estiver preenchido (para ter o registro do dia)
            // Ou, mais simples: sempre insere/atualiza para ter o registro do dia, mesmo que seja folga e tudo NULL.
            // Decisão: Inserir/Atualizar sempre, para que o profissional tenha um registro para cada dia da semana.
            $wpdb->insert( $table_name_horarios, $dados_db, $formatos_db );
        }
    }

    $redirect_url = add_query_arg( array(
        'agendamentos_wp_message' => __( 'Horários salvos com sucesso!', 'agendamentos-wp' ),
        'agendamentos_wp_message_type' => 'success',
    ), $redirect_url );
    wp_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_agendamentos_wp_save_horarios', 'agendamentos_wp_handle_save_horarios' );

?>
