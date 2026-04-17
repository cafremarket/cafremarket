<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Help Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to display application language.
    | You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */
'custom_css_help_text' => 'Adicione o seu CSS personalizado na caixa. Isto irá sobrepor o design do tema.',

'custom_css_guideline_for_merchant' => 'Personalize facilmente a aparência da página do seu perfil aplicando os seus próprios estilos CSS. Ajuste a cor do nome da sua loja e redimensione o seu logótipo de forma simples seguindo os passos abaixo.',

'custom_css_guideline' => 'Personalize facilmente a aparência do seu marketplace aplicando os seus próprios estilos CSS. Se quiser alterar a cor de fundo da barra de navegação principal, do botão de pesquisa ou do rodapé, é assim que pode fazê-lo.',

'trust_badge_size' => 'O tamanho da imagem do selo de confiança deve ser 32x32px e .png.',

'add_input_field' => 'Adicionar campo de entrada',

'remove_input_field' => 'Remover este campo de entrada',

'marketplace_name' => 'O nome do marketplace. Os visitantes irão ver este nome.',

'system_legal_name' => 'O nome legal da empresa',

'min_pass_length' => 'Mínimo 6 caracteres',

'role_name' => 'O título da função do utilizador',

'role_type' => 'Plataforma e Comerciante. O tipo de função "plataforma" está disponível apenas para o utilizador principal da plataforma; um comerciante não pode usar esta função. O tipo de função "Comerciante" estará disponível quando um comerciante adicionar um novo utilizador.',

'role_level' => 'O nível da função será usado para determinar quem pode controlar quem. Exemplo: Um utilizador com nível de função 2 não pode modificar um utilizador com nível de função 1. Deixe em branco se a função for para utilizadores finais.',

'you_cant_set_role_level' => 'Apenas utilizadores de nível superior podem definir este valor.',

'cant_edit_special_role' => 'Este tipo de função não é editável. Tenha cuidado ao modificar as permissões desta função.',

'set_role_permissions' => 'Defina as permissões da função com muito cuidado. Escolha o "Tipo de Função" para obter os módulos apropriados.',

'permission_modules' => 'Ative o módulo para definir permissões para o módulo',

'shipping_rate_delivery_takes' => 'Seja específico; o cliente irá ver isto.',

'type_dbreset' => 'Digite exatamente a palavra "RESET" na caixa para confirmar a sua intenção.',

    'type_environment' => 'Type the exact word "ENVIRONMENT" in the box to confirm your wish.',

    'type_uninstall' => 'Type the exact word "UNINSTALL" in the box to confirm.',

    'module' => [
        'name' => 'All users under this role will be able to do specified actions to manage :module.',

        'access' => [
            'common' => 'This is a :access module. That means both platform users and merchant users can get access.',

            'platform' => 'This is a :access module. That means only platform users can get access.',

            'merchant' => 'This is a :access module. That means only merchant users can get access.',
        ],
    ],

'currency_iso_code' => 'Código ISO 4217. Por exemplo, o dólar dos Estados Unidos tem o código USD e a moeda do Japão tem o código JPY.',

'country_iso_code' => 'Código ISO 3166_2. Por exemplo, para os Estados Unidos da América o código é US.',

'currency_subunit' => 'A subunidade que é uma fração da unidade base. Por exemplo: cent, cêntimo, paisa.',

'currency_symbol_first' => 'O símbolo será colocado à esquerda? Exemplo: $13,21.',

'currency_decimalpoint' => 'Exemplo: 13.21, 13,21.',

'currency_thousands_separator' => 'Exemplo: 1,000; 1.000; 1 000.',

'cover_img_size' => 'O tamanho da imagem de capa deve ser 1280x300px.',

'featured_img_size' => 'O tamanho da imagem em destaque deve ser 285x190px.',

'brand_featured_img_size' => 'O tamanho da imagem em destaque da marca deve ser 380x160px.',

'top_bar_img_size'  => 'O tamanho da imagem da barra superior deve ser 90x1500px.',

'featured_image' => 'Esta imagem será exibida na secção de Categorias em Destaque na página inicial.',

'brand_featured_image' => 'Esta imagem será exibida na secção de Marcas em Destaque na página inicial.',

'slug' => 'O slug é geralmente uma URL amigável para motores de busca.',

'shop_slug' => 'Este será usado como URL da sua loja. Não poderá alterá-lo mais tarde. Seja criativo ao escolher o slug da sua loja.',

'shop_url' => 'O caminho completo para a página de destino da loja. Não deve alterar o slug da loja, pois isso pode prejudicar o seu SEO.',

'shop_timezone' => 'O fuso horário não afetará a loja nem o marketplace. Serve apenas para conhecer melhor a sua loja.',

    // 'website' => 'Homepage link',

    'url' => 'Web address',

    'optional' => '(optional)',

    'use_markdown_to_bold' => 'Add ** both side of important keyword to highlight',

    // 'help_doc_link' => 'Help document link',

    'blog_feature_img_hint' => 'The image size should be 960x480px',

    'code' => 'Code',

    'brand' => 'The brand of the product. Not required but recommended',

    'shop_name' => 'The brand or display name of the shop',

    'shop_status' => 'If active, the shop will be live immediately.',

    'shop_maintenance_mode_handle' => 'If maintenance mode is on, the shop will be offline and all listings will be down from the marketplace until maintenance off.',

    'system_maintenance_mode_handle' => 'If maintenance mode is on, the marketplace will be offline and the maintenance mode flag will be shown to the visitors. Still merchants can access their admin panel.',

    'system_physical_address' => 'The physical location of the marketplace/office',

    'system_email' => 'Contact us messages and all system notifications will be send to this email address, accept support emails(if set)',

    'system_timezone' => 'This system will use this timezone to operate.',

    'system_currency' => 'The marketplace currency',

    'system_slogan' => 'The tagline that describe your marketplace most',

    'system_length_unit' => 'Unit of length will be use all over the marketplace.',

    'system_weight_unit' => 'Unit of weight will be use all over the marketplace.',

    'system_volume_unit' => 'A unidade de volume será usada em todo o marketplace.',

    'ask_customer_for_email_subscription' => 'Quando um novo cliente registar uma conta, pergunte se deseja receber promoções e outras notificações por e-mail. Desligar esta opção resultará em subscrição automática. Nesse caso, deixe claro na secção de termos e condições.',

    'allow_guest_checkout' => 'Isto permitirá que os clientes finalizem a compra sem se registarem no site.',

    'vendor_can_view_customer_info' => 'Isto permitirá que os vendedores vejam informações detalhadas do cliente na página da encomenda. Caso contrário, apenas o nome, e-mail e endereços de faturação/envio serão visíveis.',

    'system_pagination' => 'Defina o valor de paginação para as tabelas de dados no painel de administração.',

    'subscription_name' => 'Dê um nome significativo ao plano de subscrição.',

    'subscription_plan_id' => 'Se estiver a usar um sistema de subscrição baseado em Stripe, insira aqui o ID do plano Stripe.',

    'featured_subscription' => 'Deve haver apenas uma subscrição em destaque.',

    'subscription_cost' => 'O custo da subscrição por mês.',

    'team_size' => 'O tamanho da equipa é o número total de utilizadores que podem registar-se sob esta equipa.',

    'inventory_limit' => 'Número total de listagens. Uma variante do mesmo produto será considerada como um item diferente.',

    'marketplace_commission' => 'Percentagem do valor do item da encomenda cobrada pelo marketplace.',

    'transaction_fee' => 'Se desejar cobrar uma taxa fixa por cada transação.',

    'subscription_best_for' => 'Cliente alvo para este pacote. Isto será visível para o cliente.',

    'config_return_refund' => 'Política de devolução e reembolso da sua loja. Por favor, leia a política do marketplace antes de especificar a sua.',

    'config_trial_days' => 'O comerciante será cobrado após o período de teste. Se não recolher o cartão antecipadamente, a conta do comerciante será congelada após este período.',

   'charge_after_trial_days' => 'Será cobrado após o período de teste de :days dias.',

    'required_card_upfront' => 'Pretende recolher os dados do cartão quando o comerciante se registar?',

    'leave_empty_to_save_as_draft' => 'Deixe vazio para guardar como rascunho',

    'logo_img_size' => 'O tamanho do logótipo deve ser, no mínimo, 300x300px',

    'brand_logo_size' => 'O tamanho do logótipo da marca deve ser 120x40px e em .png',

    'brand_icon_size' => 'O tamanho do ícone da marca deve ser 32x32px e em .png',

    'config_alert_quantity' => 'Será enviado um email de notificação quando o stock ficar abaixo da quantidade de alerta',

    'config_max_img_size_limit_kb' => 'O tamanho máximo de imagem que o sistema pode carregar para produto/inventário/logótipo/avatar. O tamanho é em kilobytes.',

    'config_max_number_of_inventory_imgs' => 'Escolha quantas imagens podem ser carregadas para um único item de inventário.',

    'config_address_default_country' => 'Defina este valor para preencher o formulário de morada mais rapidamente. Obviamente, o utilizador pode alterar o valor ao adicionar uma nova morada.',

    'config_address_default_state' => 'Defina este valor para preencher o formulário de morada mais rapidamente. Obviamente, o utilizador pode alterar o valor ao adicionar uma nova morada.',

    'config_show_address_title' => 'Mostrar/Ocultar título da morada ao visualizar/imprimir uma morada.',

    'config_address_show_country' => 'Mostrar/Ocultar o nome do país ao visualizar/imprimir uma morada. Útil se o seu marketplace estiver dentro de uma pequena região.',

    'config_address_show_map' => 'Pretende mostrar um mapa com as moradas? Esta opção irá gerar o mapa usando o Google Maps.',

    // 'system_date_format' => 'Set the date format for the marketplace. Example: 2018-05-13, 05-13-2018, 13-05-2018',

    // 'config_date_separator' => 'Example: 2018-05-13, 2018.05.13, 2018/05/13',

    // 'system_time_format' => 'Set the time format for the marketplace. Example: 01:00pm or 13:00',

    // 'config_time_separator' => ' Example: 07:00am or 07.00am',

    'config_show_currency_symbol' => 'Pretende mostrar o símbolo da moeda ao apresentar um preço? Exemplo: $123',

    'config_show_space_after_symbol' => 'Pretende formatar o preço colocando um espaço após o símbolo? Exemplo: $ 123',

    'config_decimals' => 'Quantos dígitos pretende mostrar após o ponto decimal? Exemplo: 13.21, 13.123',

    // 'config_decimalpoint' => 'Example: 13.21, 13,21',

    // 'config_thousands_separator' => 'Example: 1,000, 1.000, 1 000',

    'config_gift_card_pin_size' => 'Quantos dígitos pretende gerar para o código PIN do cartão de oferta. Comprimento padrão: 10',

'config_gift_card_serial_number_size' => 'Quantos dígitos pretende gerar para o número de série do cartão de oferta. Comprimento padrão: 13',

'config_coupon_code_size' => 'Quantos dígitos pretende gerar para o código do cupão. Comprimento padrão: 8',

'shop_email' => 'Todas as notificações serão enviadas para este endereço de e-mail (inventários, encomendas, bilhetes, disputas, etc.), exceto os e-mails de apoio ao cliente (se definidos)',

'shop_legal_name' => 'O nome legal da loja',

'shop_owner_id' => 'O proprietário e super administrador da loja. Um utilizador registado como Comerciante pode possuir uma loja. Não é possível alterar esta opção mais tarde.',

    // 'shop_owner_cant_change' => 'The owner of the shop can\'t be changed. Instead you can delete the shop and create a new one.',

   'shop_description' => 'A descrição da marca da loja; esta informação será visível na página inicial da loja.',

    'attribute_type' => 'O tipo de atributo. Isto ajudará a mostrar as opções na página do produto.',

    'attribute_name' => 'Este nome será exibido na página do produto.',

    'attribute_value' => 'Este valor será exibido na página do produto como uma opção selecionável.',

    'parent_attribute' => 'A opção será exibida sob este atributo.',

    'list_order' => 'Ordem de visualização na lista.',

    // 'external_url' => 'If you own a website you can put the external link here',

    'shop_external_url' => 'Se possuir um website, pode colocar o link externo aqui; a URL poderá ser definida como a página de entrada da loja.',

    'product_name' => 'Os clientes não verão isto. Este nome ajuda apenas os comerciantes a localizar o item para listagem.',

    'product_featured_image' => 'Os clientes não verão isto. Isto ajuda apenas os comerciantes a localizar o item para listagem.',

    'product_images' => 'Os clientes verão estas imagens apenas se a listagem do comerciante não tiver imagens para exibir.',

    'product_active' => 'Os comerciantes encontrarão apenas itens ativos.',

    'product_slug' => 'Isto será usado como a URL do produto. Não poderá alterá-lo posteriormente. Seja criativo ao escolher o slug para o seu produto.',

    'product_description' => 'Os clientes verão isto. Esta é a descrição principal e comum do produto.',

    'model_number' => 'Um identificador do produto fornecido pelo fabricante. Não é obrigatório, mas recomendado.',

    'gtin' => 'O Número Global de Item Comercial (GTIN) é um identificador único de um produto no mercado global. Se desejar obter um código ISBN ou UPC para o seu produto, poderá encontrar mais informações nos seguintes sites: http://www.isbn.org/ e http://www.uc-council.org/',

   'mpn' => 'O Número de Peça do Fabricante (MPN) é um identificador único emitido pelo fabricante. Pode obter MPNs junto do fabricante. Não é obrigatório, mas é recomendado',

    'sku' => 'SKU (Unidade de Manutenção de Stock) é um identificador específico do vendedor. Ajuda a gerir o seu inventário.',

'isbn' => 'O Número Padrão Internacional de Livros (ISBN) é um código de barras único para identificar comercialmente um livro. Cada código ISBN identifica unicamente um livro. Os ISBN têm 10 ou 13 dígitos. Todos os ISBN atribuídos após 1 de janeiro de 2007 têm 13 dígitos. Normalmente, o ISBN é impresso na contracapa do livro.',

'ean' => 'O Número Europeu de Artigo (EAN) é um padrão de código de barras, um código de identificação de produto de 12 ou 13 dígitos. Pode obter EANs junto do fabricante. Se os seus produtos não tiverem EANs do fabricante e precisar de comprar códigos EAN, consulte GS1 UK http://www.gs1uk.org',

'upc' => 'Código Universal de Produto (UPC), também chamado de GTIN-12 ou UPC-A. É um identificador numérico único para produtos comerciais, normalmente associado a um código de barras impresso em mercadorias de retalho.',
    'meta_title' => 'As etiquetas de título — tecnicamente designadas por elementos de título — definem o título de um documento. As etiquetas de título são frequentemente utilizadas em páginas de resultados de motores de busca (SERPs) para exibir excertos de pré-visualização de uma determinada página e são importantes tanto para SEO como para partilha social.',

    'meta_description' => 'As meta descrições são atributos HTML que fornecem explicações concisas sobre o conteúdo das páginas web. As meta descrições são normalmente utilizadas em páginas de resultados de motores de busca (SERPs) para exibir excertos de pré-visualização de uma determinada página.',

    'catalog_min_price' => 'Defina um preço mínimo para o produto. Os vendedores podem adicionar inventário dentro destes limites de preço.',

    'catalog_max_price' => 'Defina um preço máximo para o produto. Os vendedores podem adicionar inventário dentro destes limites de preço.',

    'requires_shipping' => 'Este item requer envio.',

    'requires_shipping_with_inventory' => 'Requer envio (Os downloads devem ser desativados quando o envio estiver ativado para mostrar a secção de envio).',

    'downloadable' => 'Este item é conteúdo digital e os compradores podem descarregar o item.',

    'manufacturer_url' => 'O link do site oficial do fabricante.',

    'manufacturer_email' => 'O sistema usará este endereço de e-mail para comunicar com o fabricante.',

    'manufacturer_phone' => 'O número de telefone de suporte do fabricante.',

    'supplier_email' => 'O sistema usará este endereço de e-mail para comunicar com o fornecedor.',

    'supplier_contact_person' => 'Pessoa de contacto',

    // 'supplier_phone' => 'The support phone number of the supplier.',

    // 'supplier_address' => 'The system will use this address to create invoice.',

    'shop_address' => 'O endereço físico da loja.',

    'payout_bank_info' => 'As suas informações bancárias ajudarão a receber o pagamento do marketplace.',

    'account_type' => 'Tipo de conta bancária, como poupança, corrente, checking, etc.',

    'account_routing_number' => 'O número de roteamento é um código de nove dígitos usado para identificar uma instituição financeira.',

    'account_swift_bic_code' => 'Um código SWIFT/BIC identifica bancos e instituições financeiras a nível global.',

    'account_iban' => 'Para a maioria dos tipos de pagamento, os remetentes devem usar o seu número IBAN.',

    'search_product' => 'Pode usar qualquer identificador GTIN como UPC, ISBN, EAN, JAN ou ITF. Também pode usar o nome e número de modelo OU parte do nome ou número de modelo.',

    'seller_description' => 'Esta é uma descrição específica do vendedor sobre o produto. O cliente verá isto.',

    'seller_product_condition' => 'Qual é a condição atual do produto?',

    'condition_note' => 'A nota de condição é útil quando o produto é usado ou recondicionado.',

    'select_supplier' => 'Campo recomendado. Isto ajudará a gerar relatórios.',

    'select_warehouse' => 'Escolha o armazém de onde o produto será enviado.',

    // 'inventory_select_tax' => 'The Tax will be added with the sale/offer price on the store. Orders created at back office will not apply the tax automatically. You need select the tax when create an order on back office. If your price inclusive the tax, then select -No Tax- option here',

    // 'select_carriers' => 'List of available carriers to ship the product. Leave blank to if the item doesn\'t require shipping',

       'select_packagings' => 'Lista de opções de embalagem disponíveis para enviar o produto. Deixe em branco para desativar a opção de embalagem',

        'available_from' => 'A data em que o stock estará disponível. Pode haver atraso se a hora do servidor for diferente da sua hora local.',

        'sale_price' => 'O preço sem qualquer imposto. O imposto será calculado automaticamente com base na zona de envio.',

        'purchase_price' => 'Campo recomendado. Isto ajuda a calcular os lucros e gerar relatórios',

        'min_order_quantity' => 'A quantidade mínima permitida para encomendas. Deve ser um valor inteiro. Padrão = 1',

        'offer_price' => 'O preço da oferta será aplicado entre as datas de início e fim da oferta',

        'offer_start' => 'Uma oferta deve ter uma data de início. Obrigatório se o campo de preço da oferta estiver preenchido',

        'offer_end' => 'Uma oferta deve ter uma data de fim. Obrigatório se o campo de preço da oferta estiver preenchido',

        'seller_inventory_status' => 'O item está disponível para venda? Um item inativo não será exibido no marketplace.',

        'stock_quantity' => 'Número de itens que possui no seu armazém',

        'offer_starting_time' => 'Hora de início da oferta',

        'offer_ending_time' => 'Hora de fim da oferta',

        'set_attribute' => 'Se o valor não estiver na lista, pode adicionar o valor apropriado apenas digitando o novo valor',

        'variants' => 'Variações do produto',

        'delete_this_combination' => 'Eliminar esta combinação',

        'remove_this_cart_item' => 'Remover este item do carrinho',

        'no_product_found' => 'Nenhum produto encontrado! Tente outra pesquisa ou adicione um novo produto',

        'not_available' => 'Não disponível!',

        'admin_note' => 'Nota do administrador não será visível para o cliente',

        'message_to_customer' => 'Esta mensagem será enviada ao cliente com o e-mail da fatura',

        'empty_cart' => 'O carrinho está vazio',

        'send_invoice_to_customer' => 'Enviar uma fatura ao cliente com esta mensagem',

        'delete_the_cart' => 'Eliminar o carrinho e prosseguir com a encomenda',

        // 'order_status_name' => 'The title of the status that will be visible everywhere.',

        // 'order_status_color' => 'The label color of the order status',

          'order_status_send_email' => 'Um e-mail será enviado ao cliente quando o estado da encomenda for atualizado',

        'order_status_email_template' => 'Este modelo de e-mail será enviado ao cliente quando o estado da encomenda for atualizado. Obrigatório se o e-mail estiver ativado para este estado',

        'update_order_status' => 'Atualizar o estado da encomenda',

        'email_template_name' => 'Dê um nome ao modelo. Isto é apenas para uso do sistema.',

        'template_use_for' => 'O modelo será utilizado por',

        'email_template_subject' => 'Isto será usado como o assunto do e-mail.',

        'email_template_body' => 'Existem alguns códigos curtos que pode usar para informação dinâmica. Verifique na parte inferior deste formulário os códigos curtos disponíveis.',

        'email_template_type' => 'O tipo de e-mail.',

        'template_sender_email' => 'Este endereço de e-mail será usado para enviar e-mails e o destinatário poderá responder a este.',

        'template_sender_name' => 'Este nome será usado como nome do remetente',

        // 'payment_method_name' => 'Name of the payment method',

        // 'payment_method_company_name' => 'The main company name',

        'packaging_name' => 'O cliente verá isto se a opção de embalagem estiver disponível no checkout da encomenda',

        'width' => 'A largura da embalagem',

        'height' => 'A altura da embalagem',

        'depth' => 'A profundidade da embalagem',

        'packaging_cost' => 'O custo da embalagem. Pode escolher se deseja cobrar este custo aos clientes ou não',

        'set_as_default_packaging' => 'Se selecionado: esta embalagem será usada como embalagem de envio padrão',

        // 'packaging_charge_customer' => 'If checked: the cost will be added with shipping when a customer place an order.',

        'shipping_carrier_name' => 'Nome da transportadora',

        // 'config_enable_shipping_method' => 'The system offers Different Shipping methods that help you utilize all your delivery and shipping needs seamlessly.',

        // 'shipping_tax' => 'Shipping tax will be added to shipping cost while checkout.',

        'shipping_zone_name' => 'Dê um nome à zona. O cliente não verá este nome.',

        'shipping_rate_name' => 'Dê um nome significativo. O cliente verá este nome no checkout. Ex.: \'envio standard\'',

        'sipping_zone_carrier' => 'Pode associar o transportador de envio. O cliente verá isto no checkout.',

        'free_shipping' => 'Se ativado, o rótulo de envio gratuito será exibido na página de listagem de produtos.',

        'shipping_rate' => 'Marque a opção \'Envio gratuito\' ou coloque 0 para envio gratuito',
        'shipping_zone_tax' => 'Este perfil de imposto será aplicado quando o cliente efetuar uma compra a partir desta zona de envio',

        'shipping_zone_select_countries' => 'Se não vir o país nas opções, provavelmente o marketplace não está operativo nessa área. Pode contactar o suporte do marketplace para solicitar a adição do país na área de negócio.',

        'rest_of_the_world' => 'Esta zona inclui todos os países e regiões dentro da área de negócio do marketplace que não estão definidos nas suas outras zonas de envio.',

        'shipping_max_width' => 'Largura máxima da embalagem suportada pelo transportador. Deixe em branco para desativar.',

        'shipping_tracking_url' => 'O \'@\' será substituído pelo número de rastreamento dinâmico',

        'shipping_tracking_url_example' => 'ex.: http://example.com/track.php?num=@',

        'order_tracking_id' => 'ID de rastreamento da encomenda fornecido pelo serviço de transporte.',

        'order_fulfillment_carrier' => 'Escolha o transportador para efetuar a entrega da encomenda.',

        'notify_customer' => 'Um e-mail de notificação será enviado ao cliente com as informações necessárias.',

        // 'order_status_fulfilled' => 'Do you want to mark the order as fulfilled when the order status changed to this?',

          'shipping_length' => 'Comprimento do produto utilizado pelos serviços de envio para calcular o custo de envio',

        'shipping_width' => 'Largura do produto utilizada pelos serviços de envio para calcular o custo de envio',

        'shipping_height' => 'Altura do produto utilizada pelos serviços de envio para calcular o custo de envio',

        'shipping_weight' => 'O peso será utilizado para calcular o custo de envio.',

        'order_number_prefix_suffix' => 'O prefixo e o sufixo serão adicionados automaticamente para formatar todos os números de encomenda. Deixe em branco se não quiser formatar os números de encomenda.',

        'customer_not_see_this' => 'O cliente não verá isto',

        'customer_will_see_this' => 'Os clientes verão isto',

        'refund_select_order' => 'Selecione a encomenda que pretende reembolsar',

        'refund_order_fulfilled' => 'A encomenda foi enviada para o cliente?',

        'refund_return_goods' => 'O artigo foi devolvido para si?',

        'customer_paid' => 'O cliente pagou <strong><em> :amount </em></strong>, incluindo todos os impostos, despesas de envio e outros.',

        'order_refunded' => 'Reembolsado anteriormente <strong><em> :amount </em></strong> do total <strong><em> :total </em></strong>',

        'search_customer' => 'Encontre o cliente pelo endereço de e-mail, nome de utilizador ou nome completo.',

        'coupon_quantity' => 'Número total de cupões disponíveis',

        'coupon_name' => 'O nome será usado na fatura e no resumo da encomenda',

        'coupon_code' => 'O código de cupão único',

        'coupon_value' => 'O valor do cupão',

        'coupon_min_order_amount' => 'Escolha o valor mínimo da encomenda para o carrinho (opcional)',

    'coupon_quantity_per_customer' => 'Escolha quantas vezes um cliente pode utilizar este cupão. Se o deixar em branco, o cliente poderá utilizar este cupão até que o mesmo\'esteja disponível..',

  'starting_time' => 'O cupão estará disponível a partir desta hora',

'ending_time' => 'O cupão estará disponível até esta hora',

'exclude_tax_n_shipping' => 'Excluir impostos e custos de envio',

'exclude_offer_items' => 'Excluir itens que já têm uma oferta ou desconto em vigor',

'coupon_limited_to_customers' => 'Escolha se pretende que o cupão seja apenas para clientes específicos',

'coupon_limited_to_shipping_zones' => 'Escolha se pretende que o cupão seja apenas para zonas de envio específicas',

'coupon_limited_to' => 'Use o endereço de e-mail ou o nome para encontrar clientes',

'faq_placeholders' => 'Pode usar este marcador na sua pergunta e resposta; será substituído pelo valor real',

'gift_card_name' => 'O nome do cartão de oferta.',

'gift_card_pin_code' => 'O código secreto único. O código PIN é a palavra-passe do cartão. Não pode alterar este valor mais tarde.',

'gift_card_serial_number' => 'O número de série único do cartão. Não pode alterar este valor mais tarde.',

'gift_card_value' => 'O valor do cartão. O cliente receberá o mesmo montante de desconto.',

'gift_card_activation_time' => 'Hora de ativação do cartão. O cartão só poderá ser usado a partir desta hora.',

'gift_card_expiry_time' => 'Data de validade do cartão. O cartão será válido até esta hora.',

'gift_card_partial_use' => 'Permitir uso parcial do valor total do cartão',

'number_between' => 'Entre :min e :max',

    // 'default_tax_id' => 'Default tax profile will be preselected when add new inventory',

    'default_tax_id' => 'O perfil fiscal predefinido será aplicado quando a zona de envio não estiver coberta por nenhuma área fiscal.',

    'default_payment_method_id' => 'Se selecionado, o método de pagamento será pré-selecionado ao criar uma nova encomenda.',

    'config_order_handling_cost' => 'Este custo adicional será adicionado ao custo de envio de cada encomenda. Deixe em branco para desativar a taxa de manuseio da encomenda.',

    'default_warehouse' => 'O armazém predefinido será pré-selecionado ao adicionar novo inventário.',

    'default_supplier' => 'O fornecedor predefinido será pré-selecionado ao adicionar novo inventário.',

    'default_packaging_ids_for_inventory' => 'A embalagem predefinida será pré-selecionada ao adicionar novo inventário. Isto ajudará a adicionar inventário mais rapidamente.',

    'config_payment_environment' => 'As credenciais são para modo live ou de teste?',

    'config_enable_payment_method' => 'O sistema oferece vários tipos de gateways de pagamento. Pode ativar/desativar qualquer gateway de pagamento para controlar as opções de pagamento que o vendedor pode usar para aceitar pagamentos dos clientes.',

    'config_additional_details' => 'Exibido na página do método de pagamento, enquanto o cliente escolhe como pagar.',

    'config_payment_instructions' => 'Exibido na página de agradecimento, após o cliente ter efetuado a encomenda.',

    'config_stripe_publishable_key' => 'As chaves API publicáveis destinam-se apenas a identificar a sua conta na Stripe, não são secretas e podem ser publicadas com segurança.',

    'config_paypal_express_account' => 'Normalmente, o endereço de e-mail da sua aplicação PayPal. Crie a sua aplicação PayPal aqui: https://developer.paypal.com/webapps/developer/applications/myapps',

    'config_paypal_express_client_id' => 'O Client ID é um identificador único e longo da sua aplicação PayPal. Encontrará este valor na secção My Apps & Credentials no seu painel PayPal.',

    'config_paypal_express_secret' => 'A chave secreta API do PayPal. Encontrará este valor na secção My Apps & Credentials no seu painel PayPal.',

    'config_paystack_merchant_email' => 'O e-mail do comerciante da sua conta Paystack.',

    'config_paystack_public_key' => 'A Chave Pública é um identificador único e longo da sua aplicação Paystack. Encontrará este valor na secção de Chaves API e Webhooks nas definições do seu painel Paystack.',

    'config_paystack_secret' => 'A chave secreta API da Paystack. Encontrará este valor na secção de Chaves API e Webhooks nas definições do seu painel Paystack.',

    'config_auto_archive_order' => 'Arquivar automaticamente as encomendas assim que forem concluídas e pagas. Ative esta funcionalidade para evitar arquivar manualmente cada encomenda.',

    'config_pagination' => 'Número de itens a mostrar por página nas tabelas de dados.',

    'support_phone' => 'Os clientes irão contactar este número para suporte e consultas.',

    'support_email' => 'Todos os e-mails de suporte serão recebidos neste endereço.',

    'support_phone_toll_free' => 'Se tiver um número gratuito para suporte ao cliente.',

    'default_sender_email_address' => 'Todos os e-mails automáticos enviados aos clientes serão enviados a partir deste endereço de e-mail. Também será usado quando não for possível definir um endereço de envio.',

    'default_email_sender_name' => 'Este nome será usado como remetente dos e-mails enviados a partir do endereço de e-mail padrão.',

    'google_analytic_report' => 'Deve ativar isto apenas se o sistema estiver configurado com Google Analytics. Caso contrário, podem ocorrer erros. Verifique a documentação para ajuda. Alternativamente, pode usar o sistema de relatórios incorporado na aplicação.',

    'inventory_linked_items' => 'Os itens ligados serão exibidos na página do produto como produtos frequentemente comprados em conjunto. Isto é opcional, mas importante.',

    'notify_new_message' => 'Enviar-me uma notificação quando uma nova mensagem chegar.',

    'notify_alert_quantity' => 'Enviar-me uma notificação quando algum item no meu inventário atingir o nível de quantidade de alerta.',

    'notify_inventory_out' => 'Enviar-me uma notificação quando algum item no meu inventário esgotar.',

    'notify_new_order' => 'Enviar-me uma notificação quando uma nova encomenda for feita na minha loja.',

    'notify_abandoned_checkout' => 'Enviar-me uma notificação quando um cliente abandonar o checkout de um dos meus itens.',

    'notify_when_vendor_registered' => 'Enviar-me uma notificação quando um novo vendedor se registar.',

    'notify_new_ticket' => 'Enviar-me uma notificação quando um ticket de suporte for criado no sistema.',

    'notify_new_dispute' => 'Enviar-me uma notificação quando um cliente enviar uma nova disputa.',

    'notify_when_dispute_appealed' => 'Enviar-me uma notificação quando uma disputa for apelada para revisão pela equipa do marketplace.',

    'download_template' => '<a href=":url">Descarregar um modelo CSV de exemplo</a> para ver um exemplo do formato necessário.',

    'download_category_slugs' => '<a href=":url">Descarregar slugs de categorias</a> para obter a categoria correta para os seus produtos.',

    'first_row_as_header' => 'A primeira linha é o cabeçalho. <strong>Não altere</strong> esta linha.',

    'user_category_slug' => 'Use o <strong>slug</strong> da categoria no campo de categoria.',

    'cover_img' => 'Esta imagem será exibida no topo da página :page.',

    'cat_grp_img' => 'Esta imagem será exibida no fundo do menu de categorias.',

    'cat_grp_desc' => 'O cliente não verá isto. Mas os comerciantes verão.',

    'inactive_for_back_office' => 'Se estiver inativo, os clientes ainda podem visitar a página :page. Mas os comerciantes não poderão usar esta página para listagens futuras.',

    'invalid_rows_will_ignored' => 'As linhas inválidas serão <strong>ignoradas</strong>.',

    'upload_rows' => 'Pode enviar no máximo <strong>:rows registos</strong> por lote para melhor desempenho.',

    'name_field_required' => 'O campo de nome é obrigatório.',

    'email_field_required' => 'O e-mail é obrigatório.',

    'invalid_email' => 'Endereço de e-mail inválido.',

    'invalid_category' => 'Categoria inválida.',

    'category_desc' => 'Forneça uma breve descrição. Os clientes irão vê-la.',

    'email_already_exist' => 'O endereço de e-mail já está em uso.',

    'slug_already_exist' => 'O slug já está em uso.',

    'display_order' => 'Este número será usado para organizar a ordem de visualização. O menor número será exibido primeiro.',

    'banner_title' => 'Esta linha será destacada no banner. Deixe em branco se não quiser mostrar o título.',

    'banner_description' => '(Exemplo: 50% de desconto!) Deixe em branco se não quiser mostrar isto.',

    'banner_image' => 'A imagem principal que será exibida sobre o fundo. Normalmente utiliza-se uma imagem de produto.',

    'banner_background' => 'Escolha uma cor ou carregue uma imagem como fundo.',

    'banner_group' => 'A posição do banner na vitrine. O banner não será exibido se o grupo não estiver especificado.',

    'bs_columns' => 'Quantas colunas este banner irá utilizar? O sistema usa uma grelha de 12 colunas para exibir banners.',

    'banner_order' => 'Este número será usado para organizar a ordem de visualização no grupo de banners. O menor número será exibido primeiro.',

    'banner_link' => 'Os utilizadores serão redirecionados para este link.',

    'link_label' => 'O rótulo do botão de link.',

    'slider_link' => 'Os utilizadores serão redirecionados para este link.',

    'slider_title' => 'Esta linha será destacada sobre o slider. Deixe em branco se não quiser mostrar o título.',

    'slider_sub_title' => 'A segunda linha do título. Deixe em branco se não quiser mostrar isto.',

    'slider_description' => 'Mais algumas palavras sobre o slider. Deixe em branco se não quiser mostrar a descrição.',

    'slider_image' => 'A imagem principal que será exibida como slider. É obrigatório para gerar o slider.',

    'slider_img_hint' => 'A imagem do slider deve ter 1440x350px.',

    'slider_img_hint_made_in_china' => 'A imagem do slider deve ter 780x400px.',

    'slider_order' => 'O slider será organizado por esta ordem.',

    'slider_thumb_image' => 'Esta pequena imagem será usada como miniatura. O sistema criará uma miniatura se não for fornecida.',

    // 'slider_thumb_hint' => 'It can be 150x59px',

    'variant_image' => 'The image of the variant',

    // Version 1.3.0
    'empty_trash' => 'Empty the trash. All items on the trash will be deleted permanently.',

    'hide_trial_notice_on_vendor_panel' => 'Hide trial notice on vendor panel',

    'language_order' => 'The position you want to show this language on the language option. The smallest number will display first.',

    'locale_active' => 'Do you want to show this language on the language option?',

    'locale_code' => 'The locale code, the code must have the same name as the language folder.',

    'locale_code_example' => 'Example for English the code is <em>en</em>',

    'new_language_info' => 'A new language will not affect the system unless you really do the transaction of the language directory. Check the documentation for detail.',

    'php_locale_code' => 'The PHP locale code for system use like translating date, time etc. Please find the full list of the PHP locale code on the documentation.',
    // 'php_locale_code' => 'The PHP locale code for system use like transacting date, time etc. Please find the full list here https://github.com/ahkmunna/locale-list/blob/master/data/rw/locales.php',

    'rtl' => 'Is the language is right to left (RTL)?',

    'select_all_verification_documents' => 'Select all documents at once.',

    'system_default_language' => 'System default language',

    'update_trial_period' => 'Update trial period',

    'vendor_needs_approval' => 'If enabled, every new vendor will require manual approval from the platform admin panel to get live.',

    'verified_seller' => 'Verified Seller',

    'mark_address_verified' => 'Mark as address verified',

    'mark_id_verified' => 'Mark as ID verified',

    'mark_phone_verified' => 'Mark as phone verified',

    // Version 1.3.3
    'missing_required_data' => 'Invalid data, Some required data is missing.',

    'invalid_catalog_data' => 'Invalid catalog data, Recheck the GTIN and other information.',

    'product_have_to_be_catalog' => 'The product have to be present in the <strong>catalog</strong> system. otherwise it will not upload.',

    'need_to_know_product_gtin' => 'You need to know the <strong>GTIN</strong> of the items before upload.',

    'multi_img_upload_instruction' => 'You can upload a maximum of :number images and each file size can not exceed :size KB. Please upload an image with dimensions of :dimension pixels.',

    'number_of_img_upload_required' => 'You must select at least <b>{n}</b> {files} to upload. Please retry your upload!',

    'msg_invalid_file_extension' => 'Invalid extension for file {name}. Only <b>{extensions}</b> files are supported.',

    'number_of_img_upload_exceeded' => 'You can upload a maximum of <b>{m}</b> files (<b>{n}</b> files detected).',

    'msg_invalid_file_too_large' => 'File {name} (<b>{size} KB</b>) exceeds maximum allowed upload size of <b>{maxSize} KB</b>. Please retry your upload!',

    'required_fields_csv' => 'These fields are <strong>required</strong> <em>:fields</em>.',

    'seller_condition_note' => 'Input more details about the item condition. This will help customers to understand the item.',

    // Version 1.4.0
    'active_business_zone' => 'Your business operation area. Vendors will be able to create shipping zones within active areas only.',

    'config_show_seo_info_to_frontend' => 'Show SEO info like the meta title, meta description, tags to the frontend.',

    'config_can_use_own_catalog_only' => 'If enabled, the vendors can use only his/her own catalog product to create listings (The catalog system needs to be enabled).',

    'catalog_system_enable_disable' => 'When the option is turned on, vendors must add products to the inventory before customers can see them. If the option is off, vendors can add products directly without including them in the inventory.',

    'currency_iso_numeric' => 'ISO 4217 numeric code. For example: USD = 840 and JPY = 392',

    'country_iso_numeric' => 'ISO 3166-1 numeric code. For example: USA = 840 and JAPAN = 392',

    'currency_active' => 'Active currencies will be shown on the marketplace.',

    'country_active' => 'Active currencies will be included in business area.',

    'currency_symbol' => 'The currency symbol',

    'currency_disambiguate_symbol' => 'Example: USD = US$ and BDT = BD$',

    'currency_html_entity' => 'Example: JPY = ¥ and INR = ₹',

    'currency_smallest_denomination' => 'The smallest denomination of the currency. Default value is 1',

    'currency_subunit_to_unit' => 'The number of subunits requires for a single unit. Default value is 100',

    'eea' => 'European Economic Area',

    'support_agent' => 'The support agent will get all the support notifications. If not set, the merchant will get all notifications.',

    'show_refund_policy_with_listing' => 'Show the return and refund policy on the product description page on frontend.',

    'show_shop_desc_with_listing' => 'Show the shop description on the product description page on frontend.',

    'shipping_zone_select_states' => 'If you don\'t see the option you\'re looking for, probably the marketplace is not operational in that area. You can contact the marketplace support admin to make a request to add the area.',

    'marketplace_business_area' => 'The marketplace business area',

    'notify_new_chat' => 'Send me an email notification when a new chat message arrived',

    'not_in_business_area' => 'This area is not in marketplace\'s active business area. Maybe recently removed by the marketplace admin.',

    'region_iso_code' => 'The region ISO code must have to be right. Read *Business Area* section on the documentation to get help.',

    'subscribers_count' => 'Number of active subscribers',

    'this_plan_has_active_subscribers' => 'This plan can not be deleted because it has active subscribers.',

    'max_chat_allowed' => 'Maximum of :size characters.',

    'mobile_slider_image' => 'The slider image for mobile app. The system will hide this slider on mobile if not provided. Keep the ratio 2:1 in size, which means the width of the image should be double of its height.',

    'config_can_cancel_order_within' => 'Customers will be able to cancel the order within this time after placing the order. Keep it empty to allow cancellation until order fulfillment. Set 0 to disable the cancellation option. Customers can still request cancellation to the vendor.',

    'mobile_app_slider_hits' => 'Keep the ratio 2:1',

    'enable_live_chat_on_platform' => 'If enabled, vendor will get the option to on/off the live chat on their product page and store page.',

    'enable_live_chat_on_shop' => 'Enable live chat on your product page and store page.',

    'package_dependency_not_loaded' => 'Dependency failed! This plugin depends on :dependency module(s).',

    'option_dependence_module' => 'Dependency failed! This option dependence :dependency module',

    'config_vendor_order_cancellation_fee' => 'The cancellation fee when a vendor cancel an order. Set 0 for no cancellation fee, keep empty to set custom fee for every order(cancellation will require admin approval)',

    'vendor_order_cancellation_fee' => 'The order cancellation fee will be charged to vendor.',

    'disabled_when_vendor_get_paid_directly' => 'Can not be enabled when vendor get paid directly!',

    'confirm_uninstall_package' => 'All data related to the :package will be lost forever! You cannot revert these data.',

    'verify_license_key' => 'We sent the license key to your email when you made purchase. If you don\'t find it, please contact the support with details.',

    'promotional_tagline' => 'The promotional tagline will be placed on the main navigation.',

    'promo_banner' => 'The promotional banner will be placed on the top section of the site.',

    'best_finds_under' => 'This is for the homepage <em>Best Finds Under</em> product carousel. The system will pick best selling items under this price limit.',

    'featured_items' => 'This is for the homepage <em>Featured</em> section. We suggest to set 5-10 items.',

    'featured_categories' => 'This is for the homepage <em>Featured Categories</em> section. We suggest to set 10-15 categories.',

    'trending_now_categories' => 'This is for the homepage <em>Trending Now Categories</em> section. We suggest to set 2-4 categories.',

    'featured_brands' => 'This is for the homepage <em>Featured Brands</em> section. We suggest to set 4 brands.',

    'featured_vendors' => 'This is for the homepage <em>Featured Vendors</em> section. Maximum 3 vendors can be chosen for this section.',

    'slider_alternative_color' => 'The color will be use for text inside the span tags.',

    'you_can_use_span_tag' => 'You can use <span> tag to highlight important words.',

    'social_auth' => 'If enable social login option will show on customer login and register page',

    'slider_text_position' => 'Set your content position on slider. default position right',

    'deal_of_the_day' => 'Just one item can be set as deal of the day.',

    'show_merchant_info_as_vendor' => 'Show seller name and other information on vendor list page and profile page.',

    'pay_in_person' => 'If enable self pickup will be active.',

    'pay_online' => 'If enable online payment wil be active.',

    'active_ecommerce' => 'If enable products will show in customer search.',

    'upload_package_zip_archive' => 'Upload the zip archive containing the plugin files only. Don\'t upload documentation or other files.',

    'help_clear_cache' => 'Clear system cache including configurations, images, routes. This action may require after you made some changes in the .env file or any config files. immediately you will notice a performance showdown for a bit but don\'t worry, it\'s just for the first load only.',

    'this_will_overwrite_by_dynamic_commission' => 'When dynamic commission plugin is active. This will overwrite by dynamic value.',

    'transaction_fee_will_charge' => 'The transaction fee will be charged even when the commission is zero.',

    'show_item_conditions' => 'Show item conditions(New, Used, Refurbished). When OFF the system will hide the option from all over the application.',

    'icon_size' => 'Icon should be a 32x32px .png image',

    'icon_image' => 'This icon image will show in category group dropdown as category group icon.',

    'changes_can_take_time' => 'The changes can take up to :time to affect the result.',

    'category_attributes' => 'List of attributes attached to this category. These attribute fields will be shown on the listing creation form.',

    'show_category_on_main_nav' => 'These categories will be linked to the front theme main navigation, keep it limited.',

    'hide_item_from_main_nav' => 'This options will be removed from the front theme main navigation',

    'hide_technical_details_on_product_page' => 'Hide technical details on the product page at the front end. Customers will not see the technical details on the product page.',

    'hide_out_of_stock_items' => 'When enabled, the system will hide out of stock items from the marketplace. Out of stock items will not be visible in search result, promotions, and any other list.',

    'shop_not_exist' => 'Shop has been removed or disabled.',

    'trending_now_category_help' => 'Trending now categories will get more attentions at front-end. keep it limited, 2-3 categories are recommended.',

    'read_carefully' => 'Read carefully!',

    'email_fill_notice' => 'Email has many functional work like notification, forgot password etc so try to keep this field fillable',

    'uninstall_license_incevio' => 'Before uninstalling the license from your system, you need to delete the installation from incevio.com self support portal.',

    'uninstall_app_license' => 'Uninstalling the license will allow you to re-install. This will invalidate the current installation and immediately redirect you to the installation page. To prevent any unforeseen issues, kindly remove the old installation database and files if you\'re installing in a different location.',

    'cant_revert_action' => 'You can\'t revert this action.',

    'update_app_license' => 'Updates the license if IP address of your server was changed, so script continues to work on new IP.',

    'reset_app_license' => 'Uninstalling the license will allow you to re-install the script and the current installation will stop working immediately. Please remove the old installation files to avoid unexpected issues with new installation.<br/><b>You can\'t revert this action.</b>',

    'need_help' => 'Need help? Click here',

    'warning' => 'Warning',

    'regenerate_app_key' => 'Generate the secret keys to establish a secure communication channel between your marketplace and mobile app for sharing sensitive information. These keys are crucial during the mobile app build process. Once the app is built and published, please refrain from changing these values to prevent app crashes.',

    'download_limit' => 'Set the number, how many times you want to permit to download this product for per customer after purchase. Keep it empty if want to permit unlimited download',

    'shop_payout_instruction' => 'Enter payout account information and instructions for payout here. These instructions will be shown to admin when you will request for payment',

    'tips' => 'TIPS',

    'gtm_container_id' => 'Google tag manager container id set here after setup from tag manager',

    'select_category' => 'Select the category to get attributes.',

    'choose_attributes' => 'Choose all of your attributes and then click SET VARIANTS button to generate all possible combinations.',

    'listing_type' => 'Select the listing type of the item.',

    'config_enable_pickup_order' => 'Enable/disable pickup option for customers. If enabled, customers will get the option to choose pickup order from store while placing an order.',

    'account_needs_approval' => 'Your account needs approval from the admin.',

    'account_pending_for_approval' => 'Aguarde até que a sua conta seja aprovada. Poderá comprar produtos assim que o administrador aprovar a sua conta..',

    'customer_needs_approval' => 'If enabled the customer will need approval from the admin to make purchases in the marketplace.',

    'additional_vendor_registration_fields' => 'Additional fields will be shown on the vendor registration form.',

    'smart_form_id_for_vendor_additional_info' => 'Enabling this shows the additional fields for registration in the merchant registration form.',

    'select_translation_language' => 'Select the language you want to translate. When customers who have the selected language enabled, visit the site, they will see the translated content.',

    'inventory_translation_general_info' => 'All the translations',

    'inventory_has_to_exist' => 'Inventory with the provided slug must exist for you to update it.',

    'key_features_include_example' => 'Separate each key feature with two hashtags. Example: "Feature 1##Feature 2##Feature 3" <br> will be shown as separate key feature each such as:<br> Feature 1 <br> Feature 2 <br> Feature 3<br><br>Check the sample CSV file for more details.',

    'config_shipping_environment' => 'Choose the shipping environment. Test mode will be used for testing purposes (usually with testing API key). Live will be used for production purposes.',

    'show_customer_terms_and_conditions' => 'Show terms and conditions to customer. The terms and conditions will be shown on terms and condition page via a link.',

    'show_vendor_terms_and_conditions' => 'Show vendor terms and conditions to vendor. The terms and conditions will be shown on terms and condition page via a link.',
    'smart_form_id_for_customer_registration_form' => 'Enabling this shows the additional fields of the smartForm in the customer registration form.',

    'smart_form_id_for_selling_page' => 'Enabling this shows the additional fields of the smartForm in the selling page.',

    'smart_form_id_for_contact_us_page' => 'Enabling this shows the additional fields of the smartForm in the contact us page.',

    'system_customer_invoice_template' => 'Select the invoice template for customers.',

    'system_shipping_label_template' => 'Select the shipping label template for customers.',

    'config_show_empty_homepage_slider' => 'When turned on shows homepage slider even if no slider is uploaded. Otherwise hides when no slider is uploaded.',

    'order_invoice_pdf_template' => 'select the order invoice template for your shop. if not selected a default template will be used.',

    'shipping_label_pdf_template' => 'select the shipping label template for your shop. if not selected a default template will be used.',

    'stamp_img_size' => 'Size of the stamp image may affect its size in pdf Templates. Upload accordingly.',

    'stamp_img' => 'Select the stamp image for your shop. Stamp image can be used in pdf generation such as order invoices.',
    'add_shop_at_end' => 'Add <b>SHOP</b> field at the end of the CSV file header.',

    'shop_not_found' => 'The shop :shop not found',

    'subscription_plan_id_need_to_know' => 'You need to know the subscription plan id.',
];
