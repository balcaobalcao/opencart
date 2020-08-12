<?php

// Heading
$_["heading_title"] = "Balcão Balcão";

// Text
$_["text_shipping"] = "Fretes";
$_["text_success"] = "Módulo $_[heading_title] modificado com sucesso!";
$_["text_edit"] = "Editando $_[heading_title]";
$_["text_get_one"] = "Cadastre-se na $_[heading_title] e obtenha nossas vantagens.";
$_["text_sale"] = "Este pedido não foi escolhido para ser enviado pela $_[heading_title].";

// Entry
$_["entry_postcode"] = "CEP Origem";
$_["entry_additional_time"] = "Prazo Adicional";
$_["entry_order_status_send"] = "Status Envio";
$_["entry_order_status_cancel"] = "Status Cancelamento";
$_["entry_order_status_agent"] = "Status Agente Origem";
$_["entry_order_status_sent"] = "Status Encaminhado ao Destino";
$_["entry_order_status_destiny"] = "Status Agente Destino";
$_["entry_order_status_customer"] = "Status Retirado";
$_["entry_code"] = "Código da Loja";
$_["entry_token"] = "Token";
$_["entry_total"] = "Valor Mínimo";
$_["entry_geo_zone"] = "Região Geográfica";
$_["entry_status"] = "Situação";
$_["entry_sort_order"] = "Ordem";
$_["entry_tax"] = "Taxa";
$_["entry_endpoint"] = "Url do Endpoint";
$_["entry_sale_tracking_code"] = "Código de Rastreio";
$_["entry_sale_no_tracking_code"] = "<b>Pedido ainda não enviado para a Balcão Balcão.</b>";
$_["entry_sale_shipping"] = "Frete";
$_["entry_sale_address"] = "Endereço";
$_["entry_sale_price"] = "Valor";
$_["entry_sale_deadline"] = "Prazo";
$_["entry_sale_tag_print"] = "Imprimir Etiqueta";
$_["entry_sale_print"] = "Imprimir";

// Help Toolips
$_["help_postcode"] = "CEP do seu centro de distribuição ou local de origem dos produtos.";
$_["help_additional_time"] = "Número de dias que serão adicionados ao prazo (geralmente a quantidade de dias que você precisa para fabricar, preparar e entregar os produtos para o agente de origem da $_[heading_title]). Padrão: 0.";
$_["help_order_status_send"] = "Situação do pedido que cadastra o pedido na $_[heading_title] (podem ser múltiplas). Padrão: Em Produção.";
$_["help_order_status_cancel"] = "Situação do pedido que cancela o pedido na $_[heading_title] (podem ser múltiplas). Padrão: Cancelado.";
$_["help_order_status_agent"] = "Situação do pedido que é definida quando o agente de origem confirma o recebimento dos produtos para envio. Padrão: Despachado.";
$_["help_order_status_sent"] = "Situação do pedido que é definida quando o agente de origem encaminha ao agente de destino. Padrão: Despachado.";
$_["help_order_status_destiny"] = "Situação do pedido que é definida quando o pedido chega no agente de destino. Padrão: Completo.";
$_["help_order_status_customer"] = "Situação do pedido que é definida quando é o pedido foi retirado pelo cliente.";
$_["help_code"] = "Código de identificação da sua loja com a $_[heading_title].";
$_["help_token"] = "Código de acesso para comunicação entre a $_[heading_title] e sua loja.";
$_["help_total"] = "Valor mínimo necessário para exibir as opções da $_[heading_title].";
$_["help_tax"] = "Embutir taxa da $_[heading_title] sobre o valor do frete. Se ativo, o cliente recebe a taxa acrescida no valor do frete. Se inativo, a loja assume a taxa.";
$_["help_endpoint"] = "Url da API da $_[heading_title]. Permite definir em qual ambiente da $_[heading_title] deve enviar as solicitações";

// Error
$_["error_permission"] = "Atenção: Você não tem permissão para modificar a extensão da $_[heading_title]!";
$_["error_postcode"] = "$_[entry_postcode] deve ser inserido no seguinte formato: 00000-000!";
$_["error_additional_time"] = "$_[entry_additional_time] é obrigatório!";
$_["error_order_status_send"] = "Por favor selecione pelo menos uma $_[entry_order_status_send]!";
$_["error_order_status_cancel"] = "Por favor selecione pelo menos uma $_[entry_order_status_cancel]!";
$_["error_order_status_agent"] = "$_[entry_order_status_agent] é obrigatório!";
$_["error_order_status_sent"] = "$_[entry_order_status_sent] é obrigatório!";
$_["error_order_status_destiny"] = "$_[entry_order_status_destiny] é obrigatório!";
$_["error_order_status_customer"] = "$_[entry_order_status_customer] é obrigatório!";
$_["error_token"] = "$_[entry_token] é obrigatório!";
$_["error_code"] = "$_[entry_code] é obrigatório!";
$_["error_endpoint"] = "$_[entry_endpoint] é obrigatório!";
