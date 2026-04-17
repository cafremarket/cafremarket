<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notifications Email Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the Notification library to build
    | the Notification emails. You are free to change them to anything
    | you want to customize your views to better match your platform.
    | Supported colors are blue, green, and red.
    |
    */

    // Auth Notifications
    'password_updated' => [
        'subject' => 'A sua palavra-passe da :marketplace foi atualizada com sucesso!',
        'greeting' => 'Olá :user!',
        'message' => 'A palavra-passe de acesso à sua conta foi alterada com sucesso. Se não foi você quem efetuou esta alteração, por favor contacte-nos imediatamente. Clique no botão abaixo para aceder à sua página de perfil.',
        'button_text' => 'Visitar o seu perfil',
    ],

    // Billing Notifications
    'invoice_created' => [
        'subject' => 'Fatura da taxa mensal de subscrição da :marketplace',
        'greeting' => 'Olá :merchant!',
        'message' => 'Obrigado pelo seu contínuo apoio. Anexámos uma cópia da sua fatura para os seus registos. Caso tenha alguma dúvida ou preocupação, por favor não hesite em contactar-nos.',
        'button_text' => 'Ir para o Painel de Controlo',
    ],

    // Customer Notifications
    'customer_registered' => [
       'subject' => 'Bem-vindo ao marketplace :marketplace!',
        'greeting' => 'Parabéns :customer!',
        'message' => 'A sua conta foi criada com sucesso! Clique no botão abaixo para verificar o seu endereço de e-mail.',
        'button_text' => 'Verificar conta',
    ],

    'customer_updated' => [
        'subject' => 'Informações da conta atualizadas com sucesso!',
        'greeting' => 'Olá :customer!',
        'message' => 'Esta é uma notificação para informar que a sua conta foi atualizada com sucesso!',
        'button_text' => 'Visitar o seu perfil',
    ],

    'customer_password_reset' => [
        'subject' => 'Notificação de redefinição de palavra-passe',
        'greeting' => 'Olá!',
        'message' => 'Está a receber este e-mail porque recebemos um pedido de redefinição da palavra-passe da sua conta. Se não foi você quem solicitou esta redefinição, por favor ignore esta notificação. Não é necessária nenhuma ação adicional.',
        'button_text' => 'Redefinir palavra-passe',
    ],

    // Dispute Notifications
    'dispute_acknowledgement' => [
        'subject' => 'ID Nº: :order_id] A contestação foi submetida com sucesso',
        'greeting' => 'Olá :customer',
        'message' => 'Esta é uma notificação para informar que recebemos a sua disputa referente ao Pedido com ID: :order_id. A nossa equipa de suporte entrará em contacto consigo o mais breve possível.',
        'button_text' => 'Ver a disputa',
    ],

    'dispute_created' => [
        'subject' => 'Nova disputa com ID Nº Pedido: :order_id',
        'greeting' => 'Olá :merchant!',
        'message' => 'Recebeu uma nova disputa referente ao Pedido com ID: :order_id. Por favor, reveja e resolva a questão com o cliente.',
        'button_text' => 'Ver a disputa',
    ],

    'dispute_updated' => [
        'subject' => '[ID do Pedido: :order_id] Estado da disputa atualizado!',
        'greeting' => 'Olá :customer!',
        'message' => 'A disputa referente ao Pedido com ID :order_id foi atualizada com a seguinte mensagem do vendedor: ":reply". Por favor, consulte abaixo e contacte-nos caso necessite de assistência.',
        'button_text' => 'Ver a disputa',
    ],

    'dispute_appealed' => [
        'subject' => '[ID do Pedido: :order_id] Disputa apelada!',
        'greeting' => 'Olá!',
        'message' => 'A disputa referente ao Pedido com ID :order_id foi apelada com a seguinte mensagem: ":reply". Por favor, consulte abaixo os detalhes.',
        'button_text' => 'Ver a disputa',
    ],

    'appealed_dispute_replied' => [
        'subject' => '[ID do Pedido: :order_id] Nova resposta para a disputa apelada!',
        'greeting' => 'Olá!',
        'message' => 'A disputa referente ao Pedido com ID :order_id recebeu a seguinte resposta: </br></br> ":reply"',
        'button_text' => 'Ver a disputa',
    ],

    // Inventory
    'low_inventory_notification' => [
        'subject' => 'Alerta de stock baixo!',
        'greeting' => 'Olá!',
        'message' => 'Um ou mais dos seus produtos estão com stock baixo. É hora de adicionar mais stock para manter os itens ativos no marketplace.',
        'button_text' => 'Atualizar stock',
    ],

    'inventory_bulk_upload_proceed_notice' => [
        'subject' => 'O seu pedido de importação em massa de inventário foi processado.',
        'greeting' => 'Olá!',
        'message' => 'Temos o prazer de informar que o seu pedido de importação em massa de inventário foi processado. Número total de linhas importadas com sucesso na plataforma: :success, Número de linhas com falha: :failed',
        'failed' => 'Por favor, consulte o ficheiro em anexo para as linhas com falha.',
        'button_text' => 'Ver Inventário',
    ],

    // Message Notifications
    'new_message' => [
        'subject' => ':subject',
        'greeting' => 'Olá :receiver',
        'message' => ':message',
        'button_text' => 'View the message on site',
    ],

    'message_replied' => [
        'subject' => ':user respondido :subject',
        'greeting' => 'Olá :receiver',
        'message' => ':reply',
        'button_text' => 'Veja a mensagem no site',
    ],

    // Order Notifications
    'order_created' => [
        'subject' => '[ID do Pedido: :order] A sua encomenda foi realizada com sucesso!',
        'greeting' => 'Olá :customer',
        'message' => 'Obrigado por nos escolher! A sua encomenda [ID do Pedido: :order] foi realizada com sucesso. Iremos informá-lo sobre o estado da encomenda.',
        'button_text' => 'Visitar a loja',
    ],

    'merchant_order_created_notification' => [
        'subject' => 'Nova encomenda [ID do Pedido: :order] foi realizada na sua loja!',
        'greeting' => 'Olá :merchant',
        'message' => 'Foi realizada uma nova encomenda [ID do Pedido: :order]. Por favor, verifique os detalhes da encomenda e processe-a o mais rapidamente possível.',
        'button_text' => 'Processar a encomenda',
    ],

    'order_updated' => [
       'subject' => '[ID do Pedido: :order] O estado da sua encomenda foi atualizado!',
        'greeting' => 'Olá :customer',
        'message' => 'Esta é uma notificação para informar que a sua encomenda [ID do Pedido: :order] foi atualizada. Por favor, consulte abaixo os detalhes da encomenda. Também pode verificar as suas encomendas a partir do seu painel de controlo.',
        'button_text' => 'Visitar a loja',
    ],

    'order_fulfilled' => [
       'subject' => '[ID do Pedido: :order] Seu pedido está a caminho!',
        'greeting' => 'Olá :customer',
        'message' => 'Esta é uma notificação para informar que o seu pedido [ID do Pedido :order] foi enviado e está a caminho. Veja abaixo os detalhes do pedido. Você também pode verificar seus pedidos no seu painel.',
        'button_text' => 'Visitar a loja',
    ],

    'order_paid' => [
        'subject' => '[ID do Pedido: :order] Seu pedido foi pago com sucesso!',
        'greeting' => 'Olá :customer',
        'message' => 'Esta é uma notificação para informar que o seu pedido [ID do Pedido :order] foi pago com sucesso e está a caminho. Veja abaixo os detalhes do pedido. Você também pode verificar seus pedidos no seu painel.',
        'button_text' => 'Visitar a loja',
    ],

    'order_payment_failed' => [
        'subject' => '[ID do Pedido: :order] Falha no pagamento!',
        'greeting' => 'Olá :customer',
        'message' => 'Esta é uma notificação para informar que o pagamento da sua encomenda [ID da encomenda: encomenda] falhou. Veja abaixo os detalhes da encomenda. Também pode verificar os seus pedidos no seu painel..',
        'button_text' => 'Visite a loja',
    ],

      'order_assigned' => [
        'subject' => '[Pedido ID: :order] foi atribuído a si!',
        'message' => 'Esta é uma notificação para informar que um novo pedido foi atribuído a si. Por favor, verifique os detalhes do pedido.',
      ],

    'cancellation_request_acknowledgement' => [
        'subject' => '[Pedido ID: :order] o seu pedido de cancelamento foi registado com sucesso!',
        'greeting' => 'Olá :customer',
        'message' => 'Obrigado por nos escolher! O seu pedido de cancelamento para [Pedido ID :order] foi registado com sucesso. Informaremos sobre o estado do pedido em breve.',
        'button_text' => 'Visitar a loja',
    ],

    'merchant_order_cancellation_notification' => [
        'subject' => 'Um novo pedido de cancelamento foi criado na sua loja [Pedido ID: :order].',
        'greeting' => 'Olá :merchant',
        'message' => 'Foi criado um pedido de cancelamento para o pedido [Pedido ID :order]. Por favor, verifique os detalhes do pedido e responda ao pedido o mais rápido possível.',
        'button_text' => 'Responder ao pedido',
    ],

    'cancellation_request_approved' => [
        'subject' => 'O seu pedido de cancelamento [Pedido ID: :order] foi aprovado!',
        'greeting' => 'Olá :customer',
        'message' => 'O seu pedido de cancelamento dos itens [Pedido ID :order] foi aprovado pelo vendedor. Obrigado por nos escolher!',
        'button_text' => 'Visitar a loja',
    ],

    'cancellation_request_declined' => [
        'subject' => 'Tarde demais para cancelar o seu pedido [Pedido ID: :order]',
        'greeting' => 'Olá :customer',
        'message' => 'Lamentamos! Já é tarde para aceitar o seu pedido de cancelamento dos itens [Pedido ID :order] pelo vendedor. Se não desejar ficar com os itens, ainda pode efetuar a devolução.',
        'button_text' => 'Visitar a loja',
    ],

    'order_canceled' => [
        'subject' => '[Pedido ID: :order] o seu pedido foi cancelado!',
        'greeting' => 'Olá :customer',
        'message' => 'O seu pedido [Pedido ID :order] foi cancelado. Obrigado por nos escolher!',
        'button_text' => 'Visitar a loja',
    ],

    // Refund Notifications
    'refund_initiated' => [
        'subject' => '[Pedido ID: :order] um reembolso foi iniciado!',
        'greeting' => 'Olá :customer',
        'message' => 'Esta é uma notificação para informar que iniciámos um pedido de reembolso para o seu pedido :order. Um dos membros da nossa equipa irá analisar o pedido em breve. Informaremos sobre o estado do pedido.',
    ],

    'refund_approved' => [
        'subject' => '[Pedido ID: :order] um pedido de reembolso foi aprovado!',
        'greeting' => 'Olá :customer',
        'message' => 'Esta é uma notificação para informar que aprovámos o pedido de reembolso para o seu pedido :order. O valor reembolsado é :amount. Enviámos o dinheiro para o seu método de pagamento; pode levar alguns dias até refletir na sua conta. Contacte o seu provedor de pagamento se o valor não aparecer em alguns dias.',
    ],

    'refund_declined' => [
        'subject' => '[Pedido ID: :order] um pedido de reembolso foi recusado!',
        'greeting' => 'Olá :customer',
        'message' => 'Esta é uma notificação para informar que o pedido de reembolso para o seu pedido :order foi recusado. Se não estiver satisfeito com a solução do vendedor, pode entrar em contacto diretamente com o vendedor através da plataforma ou até apresentar recurso da disputa no :marketplace. Interviremos para resolver o problema.',
    ],

    // Shop Notifications
    'shop_created' => [
        'subject' => 'Sua loja está pronta para começar!',
        'greeting' => 'Parabéns :merchant!',
        'message' => 'Sua loja :shop_name foi criada com sucesso! Clique no botão abaixo para entrar no painel administrativo da loja.',
        'button_text' => 'Ir para o Painel',
    ],

    'shop_updated' => [
        'subject' => 'Informações da loja atualizadas com sucesso!',
        'greeting' => 'Olá :merchant!',
        'message' => 'Esta é uma notificação para informar que a sua loja :shop_name foi atualizada com sucesso!',
        'button_text' => 'Ir para o Painel',
    ],

    'shop_config_updated' => [
        'subject' => 'Configuração da loja atualizada com sucesso!',
        'greeting' => 'Olá :merchant!',
        'message' => 'A configuração da sua loja foi atualizada com sucesso! Clique no botão abaixo para entrar no painel administrativo da loja.',
        'button_text' => 'Ir para o Painel',
    ],

    'shop_down_for_maintenance' => [
        'subject' => 'A sua loja está indisponível!',
        'greeting' => 'Olá :merchant!',
        'message' => 'Esta é uma notificação para informar que a sua loja :shop_name está indisponível. Nenhum cliente poderá visitar a loja até que ela volte a estar ativa novamente.',
        'button_text' => 'Ir para a página de Configuração',
    ],

    'shop_is_live' => [
        'subject' => 'A sua loja está ativa novamente!',
        'greeting' => 'Olá :merchant!',
        'message' => 'Esta é uma notificação para informar que a sua loja :shop_name voltou a estar ativa com sucesso!',
        'button_text' => 'Ir para o Painel',
    ],

    'shop_deleted' => [
        'subject' => 'A sua loja foi removida do :marketplace!',
        'greeting' => 'Olá Comerciante!',
        'message' => 'Esta é uma notificação para informar que a sua loja foi removida do nosso marketplace. Sentiremos a sua falta.',
    ],

    // System Notifications
    'system_is_down' => [
        'subject' => 'O seu marketplace :marketplace está indisponível!',
        'greeting' => 'Olá :user!',
        'message' => 'Esta é uma notificação para informar que o seu marketplace :marketplace está indisponível. Nenhum cliente poderá aceder ao marketplace até que volte a estar ativo novamente.',
        'button_text' => 'Ir para a página de configuração',
    ],

    'system_is_live' => [
        'subject' => 'O seu marketplace :marketplace está ativo novamente!',
        'greeting' => 'Olá :user!',
        'message' => 'Esta é uma notificação para informar que o seu marketplace :marketplace voltou a estar ativo com sucesso!',
        'button_text' => 'Ir para o Painel',
    ],

    'system_info_updated' => [
        'subject' => ':marketplace - Informações do marketplace atualizadas com sucesso!',
        'greeting' => 'Olá :user!',
        'message' => 'Esta é uma notificação para informar que o seu marketplace :marketplace foi atualizado com sucesso!',
        'button_text' => 'Ir para o Painel',
    ],

    'system_config_updated' => [
        'subject' => ':marketplace - Configuração do marketplace atualizada com sucesso!',
        'greeting' => 'Olá :user!',
        'message' => 'A configuração do seu marketplace :marketplace foi atualizada com sucesso! Clique no botão abaixo para aceder ao painel administrativo.',
        'button_text' => 'Ver configurações',
    ],

    'new_contact_us_message' => [
        'subject' => 'Nova mensagem através do formulário de contato: :subject',
        'greeting' => 'Olá!',
        'message_footer_with_phone' => 'Pode responder a este email ou entrar em contato diretamente através do telefone: :phone',
        'message_footer' => 'Pode responder a este email diretamente.',
    ],

    // Ticket Notifications
    'ticket_acknowledgement' => [
        'subject' => '[Ticket ID: :ticket_id] :subject',
        'greeting' => 'Olá :user',
        'message' => 'Esta é uma notificação para informar que recebemos o seu ticket :ticket_id com sucesso! A nossa equipa de suporte entrará em contacto consigo o mais breve possível.',
        'button_text' => 'Ver o ticket',
    ],

    'ticket_created' => [
        'subject' => 'Novo Ticket de Suporte [Ticket ID: :ticket_id] :subject',
        'greeting' => 'Olá!',
        'message' => 'Você recebeu um novo ticket de suporte ID :ticket_id, Remetente: :sender, do vendedor :vendor. Revise e atribua o ticket à equipa de suporte.',
        'button_text' => 'Ver o ticket',
    ],

    'ticket_assigned' => [
        'subject' => 'Um ticket foi atribuído a si [Ticket ID: :ticket_id] :subject',
        'greeting' => 'Olá :user',
        'message' => 'Esta é uma notificação para informar que o ticket [Ticket ID: :ticket_id] :subject acabou de ser atribuído a si. Revise e responda ao ticket o mais rápido possível.',
        'button_text' => 'Responder ao ticket',
    ],

    'ticket_replied' => [
        'subject' => ':user respondeu ao ticket [Ticket ID: :ticket_id] :subject',
        'greeting' => 'Olá :user',
        'message' => ':reply',
        'button_text' => 'Ver o ticket',
    ],

    'ticket_updated' => [
        'subject' => 'O Ticket [ID do Ticket: :ticket_id] :subject foi atualizado!',
        'greeting' => 'Olá :user!',
        'message' => 'Um dos seus tickets de suporte com ID #:ticket_id :subject foi atualizado. Por favor entre em contacto connosco se precisar de alguma assistência.',
        'button_text' => 'Ver o ticket',
    ],

    // User Notifications
    'user_created' => [
        'subject' => ':admin adicionou você ao marketplace :marketplace!',
        'greeting' => 'Parabéns :user!',
        'message' => 'Você foi adicionado ao :marketplace por :admin! Clique no botão abaixo para entrar na sua conta. Use a senha temporária para o login inicial.',
        'alert' => 'Não se esqueça de alterar a sua senha após o login.',
        'button_text' => 'Visitar o seu perfil',
    ],
    'user_updated' => [
        'subject' => 'Informações da conta atualizadas com sucesso!',
        'greeting' => 'Olá :user!',
        'message' => 'Esta é uma notificação para informar que a sua conta foi atualizada com sucesso!',
        'button_text' => 'Visitar o seu perfil',
    ],

    // Vendor Notifications
    'vendor_registered' => [
        'subject' => 'Novo vendedor registado!',
        'greeting' => 'Parabéns!',
        'message' => 'O seu marketplace :marketplace acaba de receber um novo vendedor com o nome da loja <strong>:shop_name</strong> e o email do comerciante é :merchant_email',
        'button_text' => 'Ir para o Painel',
    ],

    'vendor_password_reset' => [
        'subject' => 'Notificação de Redefinição de Senha',
        'greeting' => 'Olá!',
        'message' => 'Você está a receber este email porque recebemos um pedido de redefinição de senha para a sua conta. Se não solicitou a redefinição de senha, ignore esta notificação, não é necessária nenhuma ação adicional.',
        'button_text' => 'Redefinir Senha',
    ],

    // User/Merchant Notification
    'email_verification' => [
        'subject' => 'Verifique a sua conta no :marketplace!',
        'greeting' => 'Parabéns :user!',
        'message' => 'A sua conta foi criada com sucesso! Clique no botão abaixo para verificar o seu endereço de email.',
        'button_text' => 'Verificar o meu email',
    ],

    // Version 1.2.6
    'dispute_solved' => [
        'subject' => 'Disputa [Pedido ID: :order_id] foi marcada como resolvida!',
        'greeting' => 'Olá :customer!',
        'message' => 'A disputa referente ao Pedido ID: :order_id foi marcada como resolvida. Obrigado por estar connosco.',
        'button_text' => 'Ver a disputa',
    ],

    // Version 2.1.0
    'new_chat_message' => [
        'subject' => 'Nova mensagem via chat ao vivo de :sender',
        'greeting' => 'Olá :recipient!',
        'message' => 'Você recebeu uma nova mensagem através do chat ao vivo: ":message". Por favor, entre no painel da loja para responder.',
        'button_text' => 'Ver no painel',
    ],

    'otp_send' => [
      'subject' => 'Um OTP enviado pelo entregador',
'message' => 'O seu OTP é :message',
    ]

];
