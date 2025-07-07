<?php

namespace Drupal\votacao\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\votacao\Entity\Pergunta;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VotacaoController extends ControllerBase {

  private function hasVoted(Pergunta $pergunta): bool {
    return isset($_COOKIE['votou_pergunta_' . $pergunta->id()]);
  }

  private function setVoted(Pergunta $pergunta): void {
    setcookie('votou_pergunta_' . $pergunta->id(), '1', time() + 3600 * 24 * 30, '/');
  }

  public function exibirPergunta(Pergunta $vtc_pergunta): array {
    $config = $this->config('votacao.settings');
    if ($config->get('disable')) {
      throw new AccessDeniedHttpException("O sistema de votação está temporariamente desativado.");
    }
    // Verifica se a pergunta está ativa
    if (!$vtc_pergunta->get('status')->value) {
      throw new AccessDeniedHttpException('Esta votação está desativada.');
    }

    $opcoes = $vtc_pergunta->get('opcoes')->referencedEntities();
    return [
      '#theme' => 'votacao_form',
      '#pergunta' => $vtc_pergunta,
      '#opcoes' => $opcoes,
      '#show_results' => $vtc_pergunta->get('show_results')->value && $this->hasVoted($vtc_pergunta),
      '#cache' => ['max-age' => 0],
    ];
  }

  public function registrarVoto(Request $request, Pergunta $vtc_pergunta): Response {
    $config = $this->config('votacao.settings');
    if ($config->get('disable')) {
      throw new AccessDeniedHttpException("O sistema de votação está temporariamente desativado.");
    }
    // Verifica se a pergunta está ativa
    if (!$vtc_pergunta->get('status')->value) {
      throw new AccessDeniedHttpException('Esta votação está desativada.');
    }
    $opcao_id = $request->get('opcao');
    $opcao = NULL;
    foreach ($vtc_pergunta->get('opcoes')->referencedEntities() as $item) {
      if ((int) $item->id() === (int) $opcao_id) {
        $opcao = $item;
        break;
      }
    }

    if ($opcao) {
      $votos_atuais = $opcao->get('votos')->value;
      $opcao->set('votos', $votos_atuais + 1);
      $opcao->save();
      $this->messenger()
        ->addStatus($this->t('Seu voto foi registrado com sucesso.'));
      $this->setVoted($vtc_pergunta);
    }
    else {
      $this->messenger()->addError($this->t('Opção inválida.'));
    }
    return new RedirectResponse('/votacao/' . $vtc_pergunta->id());
  }

}
