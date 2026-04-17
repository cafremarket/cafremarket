<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Messages Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to display messages for any action, notices and warnings.
    | You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */

    'canceled' => 'Cancelado!',
    'confirmed' => 'Confirmado',
    'created' => ':model foi criado com sucesso!',
    'imported' => ':model foi importado com sucesso!',
    'sent' => ':model foi enviado com sucesso!',
    'updated' => ':model foi atualizado com sucesso!',
    'trashed' => ':model foi movido para a lixeira!',
    'restored' => ':model foi restaurado com sucesso!',
    'deleted' => ':model foi excluído permanentemente!',
    'card_updated' => 'Cartão de crédito atualizado com sucesso!',
    'demo_restriction' => 'Esta ação é restrita no modo demo!',
    'subscription_cancelled' => 'Assinatura cancelada!',
    'subscription_resumed' => 'Assinatura retomada com sucesso!',
    'subscribed' => 'Parabéns! Assinatura realizada com sucesso!',
    'subscription_error' => 'Erro ao criar assinatura. Entre em contato com o suporte ao cliente.',
    'cant_delete_faq_topic' => 'Não é possível excluir: exclua todas as FAQs sob o :topic e tente novamente!',
    'not_found' => ':model não existe! Tente outra busca.',
    'file_deleted' => 'O arquivo foi excluído com sucesso!',
    'updated_featured_categories' => 'Lista de categorias em destaque atualizada com sucesso!',
    'archived' => ':model foi arquivado com sucesso!',
    'fulfilled' => 'O pedido foi concluído com sucesso.',
    'fulfilled_and_archived' => 'O pedido foi concluído e arquivado com sucesso.',
    'failed' => 'A ação falhou! Algo deu errado!',
    'input_error' => 'Houve alguns problemas com os dados fornecidos.',
    'secret_logged_in' => 'Login como outro usuário realizado com sucesso.',
    'secret_logged_out' => 'Logout da conta secreta realizado.',
    'you_are_impersonated' => 'Você está utilizando uma conta em modo de impersonificação. Tenha cuidado com suas ações.',
    'profile_updated' => 'Sua conta foi atualizada com sucesso!',
    'password_updated' => 'Senha da conta atualizada com sucesso!',
    'incorrect_current_password' => 'Senha atual incorreta. Tente novamente!',
    'file_not_exist' => 'O arquivo solicitado não existe!',
    'img_upload_failed' => 'Falha ao enviar a imagem!',
    'payment_method_activation_success' => 'Ativado com sucesso! Agora você pode aceitar pagamentos usando este método.',
    'payment_method_activation_failed' => 'Falha na ativação do método de pagamento! Tente novamente.',
    'payment_method_disconnect_success' => 'Desconectado com sucesso!',
    'payment_method_disconnect_failed' => 'Este aplicativo não está conectado à conta Stripe, ou a conta não existe.',
    'invoice_sent_to_customer' => 'A fatura foi enviada ao cliente.',
    'freezed_model' => 'Este registro está congelado pelas configurações do sistema. O aplicativo precisa desse valor para funcionar corretamente.',
    'email_verification_notice' => 'Seu e-mail não foi verificado, por favor verifique para obter acesso completo.',
    'theme_activated' => 'Tema :theme ativado com sucesso!',
    'the_ip_banned' => 'O endereço IP foi banido do aplicativo.',
    'the_ip_unbanned' => 'O endereço IP foi removido da lista de bloqueio.',

    'no_billing_info' => 'Adicione informações de cobrança para continuar.',
    'no_card_added' => 'Adicione informações de cobrança para assinar.',
    'we_dont_save_card_info' => 'Não armazenamos as informações do seu cartão.',
    'plan_comes_with_trial' => 'Todo plano vem com um período de teste GRATUITO de :days dias',
    'trial_ends_at' => 'Seu período de teste termina em :ends dias!',
    'trial_expired' => 'Seu período de teste expirou! Escolha uma assinatura para continuar.',
    'generic_trial_ends_at' => 'Seu teste gratuito termina em :ends dias! Adicione informações de cobrança e escolha um plano para continuar.',
    'resume_subscription' => 'Sua assinatura termina em :ends dias! Retome sua assinatura para continuar.',
    'choose_subscription' => 'Escolha a assinatura que melhor se adapta às suas necessidades.',
    'trouble_validating_card' => 'Tivemos problemas para validar seu cartão. Pode ser que seu provedor de cartão esteja impedindo a cobrança. Entre em contato com seu provedor ou suporte ao cliente.',
    'subscription_expired' => 'Sua assinatura expirou! Escolha uma assinatura para continuar.',
    'using_more_resource' => 'Você está usando mais recursos do que o plano :plan permite. Use um plano adequado ao seu negócio.',
    'cant_add_more_user' => 'Seu plano atual não permite adicionar mais usuários. Se precisar, atualize seu plano.',
    'cant_add_more_inventory' => 'Você atingiu o limite máximo de estoque do seu plano atual. Atualize o plano para aumentar o limite.',
    'time_to_upgrade_plan' => 'É um bom momento para atualizar seu plano',
    'only_merchant_can_change_plan' => 'Apenas o proprietário da loja pode alterar o plano de cobrança/assinatura.',
    'message_send_failed' => 'Desculpe, a mensagem não pôde ser enviada agora! Tente novamente mais tarde.',
    'resource_uses_out_of' => ':used de :limit',
    'cant_charge_application_fee' => 'Quando os vendedores recebem pagamento diretamente, você não pode cobrar <b>comissão do Cafremarket</b> e <b>taxa por transação</b> com este método de pagamento. Com o módulo de carteira Cafremarket você pode cobrar a comissão e taxa do Cafremarket.',
    'license_uninstalled' => 'Licença desinstalada.',
    'license_updated' => 'Licença atualizada.',
    'take_a_backup' => 'Você pode fazer um backup do banco de dados. Certifique-se de que as preferências de backup estão configuradas antes de realizar esta ação. O <code>mysqldump</code> deve estar instalado no seu servidor. Consulte a documentação para ajuda.',
    'backup_done' => 'Backup realizado com sucesso!',

    // 'you_have_disputes_solve' => 'There are :disputes active disputes! Please review and solve disputes.',
    // 'you_have_refund_request' => 'You have :requests refund request. Please review and take action.',

    // 'action_failed'    => [
    //     'create'   => 'Create :model has been failed!',
    //     'update'   => 'Update :model has been failed!',
    //     'trash'   => ':model has been moved to trash!',
    //     'restore'  => ':model has been restored failed!',
    //     'delete'   => ':model has been deleted failed!',
    // ],

    'inventory_exist'   => 'The product is already exist in your inventory. Please update the existing list instead of creating duplicate list.',
    'inventory_not_found'   => 'The product is not found in your inventory. Please update the inventory and try again.',

    'notice' => [
        'no_billing_address' => 'This customer has no billing address set up yet. Please add a billing address before create an order.',

        'no_active_payment_method' => 'Your store has no active payment method. Please activate at least one payment method to accept order.',

        'no_shipping_option_for_the_zone' => 'No shipping zone available for this area. Please create a new shipping zone or add this shipping area to an existing zone.',

        'no_rate_for_the_shipping_zone' => 'The <strong> :zone </strong> shipping zone has no shipping rates. Please create shipping rates to accept orders from this zone.',

        'cant_cal_weight_shipping_rate' => 'Can\'t calculate weight based shipping rate. Because weight are not set for some items.',
    ],

   'no_changes' => 'Nada para mostrar',
    'no_orders' => 'Nenhum pedido encontrado!',
    'no_history_data' => 'Nenhuma informação para mostrar',
    'this_slug_taken' => 'Este slug já foi utilizado! Tente outro.',
    'slug_length' => 'O slug deve ter no mínimo três caracteres.',
    'message_count' => 'Você tem :count mensagens',
    'notification_count' => 'Você tem :count notificações não lidas',
    'alert' => 'Alerta!',
    'dispute_appealed' => 'Uma disputa foi apelada',
    'appealed_dispute_replied' => 'Nova resposta para disputa apelada',
    'thanks' => 'Obrigado',
    'regards' => 'Atenciosamente, ',
    'ticket_id' => 'ID do Ticket',
    'category' => 'Categoria',
    'subject' => 'Assunto',
    'priority' => 'Prioridade',
    'amount' => 'Valor',
    'shop_name' => 'Nome da loja',
    'customer_name' => 'Nome do cliente',
    'shipping_address' => 'Endereço de entrega',
    'billing_address' => 'Endereço de cobrança',
    'shipping_carrier' => 'Transportadora',
    'tracking_id' => 'ID de rastreamento',
    'order_id' => 'ID do pedido',
    'payment_method' => 'Método de pagamento',
    'payment_status' => 'Status do pagamento',
    'order_status' => 'Status do pedido',
    'status' => 'Status',
    'unread_notification' => 'Notificação não lida',
    'system_is_live' => 'O Cafremarket voltou ao AR!',
    'system_is_down' => 'O Cafremarket está fora do ar!',
    'system_config_updated' => 'Configuração do sistema atualizada!',
    'system_info_updated' => 'Informações do sistema atualizadas!',
    'temp_password' => 'Senha temporária: :password',
    'shop_created' => 'A loja :shop_name foi criada!',
    'shop_updated' => 'Informações da loja atualizadas!',
    'shop_config_updated' => 'Configuração da loja atualizada!',
    'shop_down_for_maintenance' => 'A loja está fora do ar!',
    'shop_is_live' => 'A loja voltou ao AR!',
    'ticket_replied' => 'O ticket foi respondido',
    'ticket_updated' => 'O ticket foi atualizado',
    'ticket_created' => 'Um novo ticket foi criado',
    'ticket_assigned' => 'Um novo ticket foi atribuído a você',
    'we_will_get_back_to_you_soon' => 'Envie-nos uma mensagem e responderemos em breve!',
    'faqs' => 'Perguntas Mais Frequentes',
    'how_the_Cafremarket_works' => 'É bom entender como o sistema funciona antes de se registrar',
    'merchant_benefits' => 'Vender online nunca foi tão fácil.',
    'import_ignored' => 'Algumas linhas foram ignoradas! Verifique as informações e tente importar novamente.',
    'import_demo_contents' => 'Importar conteúdos de demonstração removerá todos os dados do banco de dados e reiniciará todas as configurações, exceto o arquivo <small>(o .env e outros arquivos de configuração no diretório configs/)</small>. O sistema voltará a uma instalação limpa. <br/><b>Alterações aqui são irreversíveis.</b>',
    'env_saved' => 'O arquivo .env foi salvo com sucesso!',
    'modify_environment_file' => 'Tenha cuidado ao trabalhar com o arquivo `.env`, pois ele é a principal configuração do sistema. Qualquer alteração incorreta pode quebrar o sistema. Sempre faça um backup antes de modificar. <br/><b>Alterações aqui são irreversíveis.</b>',

    'be_careful_sensitive_area' => 'Tenha cuidado com suas ações! Estas configurações são muito sensíveis e o sistema pode quebrar se algo for feito incorretamente. <br/><b>Alterações aqui são irreversíveis.</b>',

    'unfulfilled_percents' => ':percent% do total de pedidos de hoje',
    'last_30_days_percents' => ':percent% :state nos últimos 30 dias',
    'stock_out_percents' => ':percent% do total de :total itens',
    'todays_sale_percents' => ':percent% :state em relação a ontem',
    'todays_order_percents' => ':percent% :state em relação a ontem',
    'no_sale' => 'Nenhuma venda em :date',
    'logged_in_as_admin' => 'Você já está logado como administrador.',

    'permission'        => [
        'denied'        => 'Permission denied!',
    ],

    // Version 1.2.4
    'listings_not_visible' => 'Seus anúncios não estão visíveis na frente da loja. Motivo: :reason',
    'no_active_payment_method' => 'Sua loja não possui nenhum método de pagamento ativo.',
    'no_active_shipping_zone' => 'Sua loja não possui nenhuma zona de entrega ativa. Crie zonas de entrega para aceitar pedidos.',

    // Version 1.2.5
    'our_shop_in_hold' => 'Sua loja está em espera! Revisaremos e aprovaremos sua loja o mais rápido possível!',
    'your_shop_in_maintenance_mode' => 'A loja está em modo de manutenção.',

    // Version 1.3.0
    'how_id_verification_helps' => 'Como a verificação de identidade ajuda',

    'how_the_verification_process_works' => 'Como o processo funciona',

    'subscription_updated' => 'A assinatura foi atualizada com sucesso!',

    'subscription_update_failed' => 'A atualização da assinatura falhou! Por favor, veja o arquivo de log para mais detalhes.',

    'pending_approvals' => '[0,1] :count aprovação pendente precisa de ação|[2,*] :count aprovações pendentes precisam de ação',

    'pending_verifications' => '[0,1] :count verificação pendente precisa de ação|[2,*] :count verificações pendentes precisam de ação',

    'verification_intro' => 'Uma vez verificado, exibiremos o selo <strong>verificado</strong> no seu negócio e na página do perfil da sua loja. Isso ajuda seu negócio a construir confiança no Cafremarket.',

    'verification_process' => '<ul>
            <li>Tire uma foto ou escaneie seu documento de identidade (passaporte, carteira de motorista ou documento emitido pelo governo) usando uma câmera HD e faça o upload</li>
            <li>Envie um comprovante de endereço (carteira de motorista, recibo de imposto sobre propriedade, conta de serviços públicos ou contrato de aluguel)</li>
            <li>Tire ou envie uma foto do seu rosto.</li>
            <li>Verificaremos se todas as fotos são da mesma pessoa.</li>
            <li>Não é possível usar o mesmo documento (carteira de motorista) para verificação de identidade e endereço.</li>
                    </ul>',

   'what_the_verification_documents_need' => 'Quais documentos de identidade formal eu preciso?',

'verification_documents' => 'Você pode usar: <ul>
        <li>seu passaporte</li>
        <li>sua carteira de motorista</li>
        <li>um documento emitido pelo governo</li>
        <li>recibo de imposto sobre propriedade</li>
        <li>conta de serviços públicos</li>
        <li>contrato de aluguel</li>
    </ul>
        </ul>
       As cartas de condução e os documentos de identidade emitidos pelo governo devem ser feitos de plástico. Todos os documentos de identidade devem ser válidos..',

    'verified_business_name_like' => 'O nome da sua empresa será apresentado assim:',

    // Version 1.3.3
    'csv_import_process_started' => 'The data has been submitted successfully. The process may take a few minimums. You\'ll get an email when it\'s done.',

    'model_has_association' => 'The :model has :associate in it. To delete this :model, please remove all :associate under the :model',

    // Version 1.4.0
    'active_worldwide_business_area' => 'The status will not affect as the Cafremarket business area is set to worldwide! To change the business area settings please check the configuration section.',

    'please_select_conversation' => 'Please select a conversation from the left.',

    'session_expired' => 'Your session has been expired! Please login.',

    'no_address_for_invoice' => 'You have no business address set up yet. Please add address now.',

    'package_settings_updated' => 'Plugin settings updated',

    'next_billing_date' => 'Your next subscription billing date is <strong>:date</strong> Please keep sufficient balance on your wallet to keep going.',

    'package_installed_success' => 'The :package module has been installed successfully!',

    'package_upgraded_success' => 'The :package module has been upgraded successfully!',

    'package_installed_already' => 'The :package module is already installed!',

    'package_uninstalled_success' => 'The :package module has been uninstalled successfully!',

    'cancellation_require_admin_approval' => 'Cancellation require admin approval. A cancellation fee may applied.',

    'a_cancellation_fee_be_charged' => 'A cancellation fee of <strong>:fee</strong> will be charged',

    'order_will_be_cancelled_instantly' => 'The order will be cancelled instantly.',

    'not_accessible_on_demo' => 'This content is not accessible on the demo mode!',

    'updated_deal_of_the_day' => 'Deal of the day updated successfully.',

    'updated_tagline' => 'Tagline updated successfully.',

    'featured_brands_updated' => 'Featured brands updated successfully.',

    'featured_vendors_updated' => 'Featured vendors updated successfully.',

    'featured_items_updated' => 'Featured items updated successfully.',

    'best_finds_under_updated' => 'Best finds under updated successfully.',

    'trending_now_category_updated' => 'Trending now category updated successfully.',

    'trending_categories_update_failed' => 'You can add maximum :limit trending category',

    'package_inactive' => ':package is inactive, please activate from admin panel.',

    'misconfigured_subscription_stripe' => 'The system found misconfigured Stripe subscriptions. Please check your settings. Read the documentation if need help.',

    'misconfigured_subscription_wallet' => 'Wallet based subscription required WALLET and LOCAL SUBSCRIPTION packages to function. Please contact support team for help. System will try to use STRIPE subscription unless these requirements met.',

    'some_item_out_of_stock' => 'Few items are not available right now. We\'ve added all available item',

    'dependent_package_failed' => 'Plugin dependency failed! need to enable and configure the :dependency module(s).',

    'misconfigured_plugin' => 'The :package needs to configure correctly! Please check the documentation and configure it correctly or contact support if need help.',

    'misconfigured_payment' => 'The :payment is misconfigured and needs to configure correctly! Please check the documentation and configure it correctly or contact support if need help.',

    'manual_payment_configure_help' => 'Set the manual payment instructions on settings. Without this configuration manual payment will not work.',

    'confirm_regenerate_key' => "Regenerating the application keys will enforce you to rebuild your application apps otherwise your apps won'n work",

    'custom_css_added' => 'Custom css added successfully',

    'custom_css_updated' => 'Custom css updated successfully',

    'custom_css_deleted' => 'Custom css deleted successfully',

    'vendor_can_use_own_catalog_only_notice' => 'The Cafremarket is configured as vendor manage the products of their own. You can off this settings <code>vendor can use own catalog only</code> from SETTINGS >> CONFIGURATION section.',

    'account_delete' => 'Your all data in our system related with your account will be removed permanently. You can\'t revert this action',

    'main_nav_category_updated' => 'Main navigation category updated successfully',

    'empty_state_message' => 'State can not be empty. Select at least one state.',

    'clear_demo_contents' => 'Clearing demo contents will remove all demo data from the database. The system will go back to a fresh installation. <br/><b>Changes made here are irreversible.</b>',

    'demo_data_imported' => 'Demo contents are imported successfully!',

    'demo_data_cleared' => 'Demo contents are cleared from your system successfully!',

    'reached_download_maximum_limit' => 'Your download maximum limit has been reached',

    'download_link_guest_customer' => 'As a non registered customer you\'ll not be able to get the link after you leave this page. Please copy the links and save it for future use. We also sent you an email with order details.',

    'download_link_loggedin_customer' => 'The download links as below. You can also download from the order detail page on your account dashboard.',

    'package_not_found' => 'The :package module not found!',

    'account_number_updated' => 'Account Number has been updated successfully',

    'updated_top_bar_banner' => 'Banner has been updated successfully',

    'switched_to_customer_successfully' => 'Switched to customer successfully',

    'customer_acc_not_exist' => 'Customer account not exist',

    'update_from_merchant_notice' => 'To update this information, please switch to your merchant account.',

    'customer_acc_created_successfully' => 'Customer account created successfully',

    'no_translation_available' => 'No translation option available! Multiple active languages are needed.',

    'uploaded_file_not_blade_file' => 'The template file must have to be a blade.php file',
];
