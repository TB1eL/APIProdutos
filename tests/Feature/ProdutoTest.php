<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Produto;

class ProdutoTest extends TestCase
{
    use RefreshDatabase; // Faltou essa trait na aula -> serve pra utilizar e dar refresh na memória temporária do sqlite


    public function test_cria_produto_com_sucesso(): void
    {
        $this->postJson('/api/produtos', [
            'nome' => 'Lajota', 
            'preco_custo' => 100,
            'preco' => 110,
        ])->assertCreated();

        $this->assertDatabaseHas('produtos', [
            'nome' => 'Lajota']);
    }

public function test_filtra_por_faixa_de_preco(): void
{
    Produto::create(['nome' => 'Barato', 'preco_custo' => 10, 'preco' => 20]);
    Produto::create(['nome' => 'Medio', 'preco_custo' => 40, 'preco' => 50]);
    Produto::create(['nome' => 'Caro', 'preco_custo' => 90, 'preco' => 100]);

    $this->getJson('/api/produtos?preco_min=30&preco_max=60')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nome', 'Medio');
}

public function test_filtra_somente_em_estoque(): void
{
    Produto::create(['nome' => 'Com estoque', 'preco_custo' => 10, 'preco' => 20, 'quantidade' => 5]);
    Produto::create(['nome' => 'Sem estoque', 'preco_custo' => 10, 'preco' => 20, 'quantidade' => 0]);

    $this->getJson('/api/produtos?em_estoque=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nome', 'Com estoque');
}

public function test_atualiza_produto_com_sucesso(): void
{
    $produto = Produto::create(['nome' => 'Lajota', 'preco_custo' => 100, 'preco' => 110]);

    $this->putJson("/api/produtos/{$produto->id}", ['nome' => 'Lajota Nova', 'preco' => 150])
        ->assertOk();

    $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'nome' => 'Lajota Nova']);
}

public function test_nao_atualiza_produto_com_margem_menor_que_10_por_cento(): void
{
    $produto = Produto::create(['nome' => 'Lajota', 'preco_custo' => 100, 'preco' => 110]);

    $this->putJson("/api/produtos/{$produto->id}", ['preco' => 105])
        ->assertStatus(422)
        ->assertJsonValidationErrors('preco');
}

public function test_remove_produto_com_sucesso(): void
{
    $produto = Produto::create(['nome' => 'Lajota', 'preco_custo' => 100, 'preco' => 110]);

    $this->deleteJson("/api/produtos/{$produto->id}")->assertNoContent();

    $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
}
}