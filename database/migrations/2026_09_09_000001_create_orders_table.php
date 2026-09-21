<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable(); // Envio de WhatsApp e contato rápido
            $table->text('delivery_address')->nullable();  // Rua, número, complemento e ponto de referência

            // Logística / Tipo de Entrega
            $table->enum('delivery_type', ['retirada', 'entrega'])->default('retirada');
            $table->foreignId('delivery_zone_id')->nullable()->constrained('delivery_zones')->nullOnDelete(); // Bairro selecionado
            $table->decimal('delivery_fee', 8, 2)->default(0); // Valor cobrado do cliente
            $table->decimal('courier_fee', 8, 2)->default(0);  // Valor repassado ao motoboy

            // Controle do Motoboy
            $table->string('courier_name')->nullable();         // Nome do entregador responsável
            $table->boolean('courier_settled')->default(false); // Flag de acerto diário liquidado

            // Pagamento e Valores
            $table->enum('payment_method', ['dinheiro', 'pix', 'cartao']);
            $table->decimal('change_for', 8, 2)->nullable();   // Troco para quanto (crítico para pagamentos em dinheiro)
            $table->decimal('total_price', 8, 2);              // Valor final (produtos + taxa de entrega)
            $table->decimal('total_profit', 8, 2);             // Lucro líquido estimado

            // Status do Pedido
            $table->enum('status', [
                'pendente',
                'em_preparo',
                'saiu_para_entrega',
                'entregue',
                'cancelado'
            ])->default('pendente');
            $table->timestamp('delivered_at')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};