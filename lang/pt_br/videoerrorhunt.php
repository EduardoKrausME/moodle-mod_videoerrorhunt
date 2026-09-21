<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * videoerrorhunt.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['adderror'] = 'Adicionar outro erro esperado';
$string['allowseek'] = 'Permitir avanço livre';
$string['alreadysubmitted'] = 'Esta caça aos erros já foi entregue.';
$string['answerkey'] = 'Erros esperados';
$string['atleastoneerror'] = 'Cadastre pelo menos um erro esperado.';
$string['backtoactivity'] = 'Voltar para a atividade';
$string['completiondetail:errors'] = 'Encontrar pelo menos {$a} erros esperados';
$string['completiondetail:percent'] = 'Assistir a pelo menos {$a}% do vídeo';
$string['completiondetail:submission'] = 'Enviar a caça aos erros';
$string['completionerrors'] = 'Exigir esta quantidade de erros encontrados (0 desativa)';
$string['completionerrorsexceed'] = 'A quantidade de erros exigida para conclusão não pode superar os erros esperados nem o limite de marcações.';
$string['completionpercent'] = 'Exigir percentual assistido';
$string['correct'] = 'Correto';
$string['difficulty'] = 'Dificuldade';
$string['directurl'] = 'URL direta do vídeo';
$string['editsettings'] = 'Editar configurações';
$string['errordescription'] = 'Descrição do professor';
$string['errorend'] = 'Aceitar até';
$string['errorpercent'] = 'Informe uma porcentagem de 0 a 100.';
$string['errorpoints'] = 'Pontos';
$string['errorsfound'] = 'Erros encontrados';
$string['errorsremaining'] = 'Erros restantes';
$string['errorstart'] = 'Aceitar de';
$string['errortitle'] = 'Erro';
$string['expectederrors'] = 'Erros esperados';
$string['expectederrorshelp'] = 'Defina cada erro esperado, a faixa de tempo aceita e sua pontuação. Os tempos aceitam MM:SS ou HH:MM:SS.';
$string['explanation'] = 'O que está errado neste momento?';
$string['explanationrequired'] = 'Escreva uma breve explicação antes de marcar um erro.';
$string['feedbackfinal'] = 'Mostrar somente após o envio final';
$string['feedbackimmediate'] = 'Mostrar imediatamente';
$string['feedbackmode'] = 'Feedback de correção';
$string['findrate'] = 'Taxa de identificação';
$string['finishhunt'] = 'Finalizar caça aos erros';
$string['found'] = 'Encontrados';
$string['grade'] = 'Nota';
$string['hardesterrors'] = 'Erros mais difíceis para a turma';
$string['huntinstruction'] = 'Este vídeo contém {$a} erros esperados. Encontre o máximo que conseguir.';
$string['huntsettings'] = 'Caça aos erros';
$string['ifoundanerror'] = 'Encontrei um erro';
$string['incorrect'] = 'Incorreto';
$string['incorrectmarks'] = 'Marcações incorretas';
$string['inprogress'] = 'Em andamento';
$string['invalidtimecode'] = 'Informe um tempo válido, como 03:14 ou 01:03:14.';
$string['invalidtimerange'] = 'O tempo final deve ser válido e igual ou posterior ao tempo inicial.';
$string['invalidvideourl'] = 'Informe uma URL HTTP ou HTTPS válida para o vídeo.';
$string['invalidvimeourl'] = 'Informe uma URL válida do Vimeo.';
$string['invalidyoutubeurl'] = 'Informe uma URL válida do YouTube.';
$string['markcorrect'] = 'Correto: esta marcação corresponde a um erro esperado.';
$string['markincorrect'] = 'Esta marcação não corresponde a um erro esperado ainda não encontrado.';
$string['marklimitreached'] = 'O número máximo de marcações foi atingido.';
$string['markregistered'] = 'Marcação registrada. A correção será mostrada após o envio final.';
$string['marksregistered'] = 'Marcações registradas';
$string['matchedto'] = 'Erro esperado correspondente';
$string['maxmarks'] = 'Número máximo de marcações';
$string['maxmarks_help'] = 'Use 0 para permitir marcações ilimitadas. Definir um limite reduz tentativas por simples tentativa e erro.';
$string['maxmarkstoosmall'] = 'A quantidade máxima de marcações deve ser 0 (ilimitada) ou pelo menos igual à quantidade de erros esperados.';
$string['maxplaybackrate'] = 'Velocidade máxima de reprodução';
$string['missed'] = 'Não encontrados';
$string['missederrorslist'] = 'Erros esperados não encontrados';
$string['modulename'] = 'Caça aos Erros em Vídeo';
$string['modulename_help'] = 'Os estudantes identificam erros esperados em um vídeo marcando momentos específicos e explicando o que observaram.';
$string['modulenameplural'] = 'Caças aos Erros em Vídeo';
$string['mustbenonnegative'] = 'Este valor deve ser zero ou maior.';
$string['noactivities'] = 'Não há atividades Caça aos Erros em Vídeo neste curso.';
$string['nomarks'] = 'Nenhuma marcação de erro foi registrada ainda.';
$string['noreportdata'] = 'Nenhum progresso de aluno foi registrado ainda.';
$string['notstarted'] = 'Não iniciado';
$string['pluginadministration'] = 'Administração do Caça aos Erros em Vídeo';
$string['pluginname'] = 'Caça aos Erros em Vídeo';
$string['points'] = 'Pontos';
$string['privacy:metadata:explanation'] = 'A explicação do estudante para o erro identificado.';
$string['privacy:metadata:grade'] = 'A nota calculada da atividade.';
$string['privacy:metadata:lastheartbeat'] = 'O último momento em que o player enviou um heartbeat de acompanhamento.';
$string['privacy:metadata:marks'] = 'Armazena marcações de erros com timestamp enviadas pelos estudantes.';
$string['privacy:metadata:percent'] = 'O percentual único do vídeo assistido.';
$string['privacy:metadata:progress'] = 'Armazena o progresso de visualização do vídeo e o estado da pontuação.';
$string['privacy:metadata:sessionkey'] = 'Um identificador aleatório da sessão atual do player de vídeo.';
$string['privacy:metadata:sessions'] = 'Armazena sequência e heartbeat das sessões de acompanhamento do player.';
$string['privacy:metadata:timepoint'] = 'O momento do vídeo selecionado pelo estudante.';
$string['privacy:metadata:userid'] = 'O ID do usuário associado aos dados.';
$string['privacy:metadata:watchedsegments'] = 'Os intervalos do vídeo realmente assistidos pelo usuário.';
$string['report'] = 'Relatório';
$string['requiredvideo'] = 'Selecione um arquivo de vídeo.';
$string['requiresubmission'] = 'Exigir envio final';
$string['result'] = 'Resultado';
$string['resumeask'] = 'Perguntar ao estudante';
$string['resumeautomatic'] = 'Retomar automaticamente';
$string['resumefromstart'] = 'Sempre iniciar do começo';
$string['resumeno'] = 'Começar do início';
$string['resumeplayback'] = 'Retomar reprodução';
$string['resumequestion'] = 'Continuar a partir de {$a}?';
$string['resumeyes'] = 'Continuar';
$string['score'] = 'Pontuação';
$string['seekblocked'] = 'Você só pode avançar para partes do vídeo que já assistiu.';
$string['sourceupload'] = 'Enviar para o Moodle';
$string['sourceurl'] = 'URL direta do vídeo';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['status'] = 'Situação';
$string['student'] = 'Aluno';
$string['studentdetail'] = 'Detalhes do aluno';
$string['studentsfound'] = 'Alunos que encontraram';
$string['submitted'] = 'Entregue';
$string['submittedmessage'] = 'Esta caça aos erros foi entregue.';
$string['time'] = 'Momento';
$string['timeline'] = 'Linha do tempo do vídeo';
$string['timerange'] = 'Faixa aceita';
$string['videoerrorhunt:addinstance'] = 'Adicionar um novo Caça aos Erros em Vídeo';
$string['videoerrorhunt:manageerrors'] = 'Gerenciar erros esperados';
$string['videoerrorhunt:submit'] = 'Enviar respostas no Caça aos Erros em Vídeo';
$string['videoerrorhunt:view'] = 'Visualizar Caça aos Erros em Vídeo';
$string['videoerrorhunt:viewreport'] = 'Visualizar relatório do Caça aos Erros em Vídeo';
$string['videoerrorhuntname'] = 'Nome da atividade';
$string['videofile'] = 'Arquivo de vídeo';
$string['videosettings'] = 'Vídeo';
$string['videosource'] = 'Fonte do vídeo';
$string['vimeourl'] = 'URL do Vimeo';
$string['watched'] = 'Assistido';
$string['wrongpenalty'] = 'Penalização por marcação incorreta';
$string['yourmarks'] = 'Suas marcações';
$string['youtubeurl'] = 'URL do YouTube';
