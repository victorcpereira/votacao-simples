<?php

declare(strict_types=1);

namespace Drupal\votacao\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\votacao\Entity\Pergunta;
use Drupal\votacao\Entity\Resposta;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Votação routes.
 */
final class VotacaoController extends ControllerBase {

  public function exibirPergunta(Pergunta $vtc_pergunta) {
    $opcoes = $vtc_pergunta->get('opcoes')->referencedEntities();

    return [
      '#theme' => 'votacao_form',
      '#pergunta' => $vtc_pergunta,
      '#opcoes' => $opcoes,
      '#cache' => ['max-age' => 0],
    ];
  }

  public function registrarVoto(Request $request, Pergunta $vtc_pergunta) {
    // Verifica se a pergunta está ativa
    if (!$vtc_pergunta->get('status')->value) {
      throw new AccessDeniedHttpException('Esta votação está desativada.');
    }

    $opcao_id = $request->get('opcao');
    $opcao = Resposta::load($opcao_id);

    if (!$opcao) {
      $this->messenger()->addError($this->t('Opção inválida.'));
      return new RedirectResponse('/votacao/' . $vtc_pergunta->id());
    }

    // Verifica se a opção realmente pertence à pergunta
    $ids_validos = array_map(fn($ent) => $ent->id(), $vtc_pergunta->get('opcoes')
      ->referencedEntities());

    if (!in_array($opcao->id(), $ids_validos)) {
      $this->messenger()->addError($this->t('Opção não pertence à pergunta.'));
      return new RedirectResponse('/votacao/' . $vtc_pergunta->id());
    }

    // Incrementa os votos
    $votos_atuais = (int) $opcao->get('votos')->value;
    $opcao->set('votos', $votos_atuais + 1);
    $opcao->save();

    // Mensagem de sucesso
    $this->messenger()
      ->addStatus($this->t('Seu voto foi registrado com sucesso.'));

    return new RedirectResponse('/votacao/' . $vtc_pergunta->id());
  }

}
