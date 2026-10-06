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
 * videorubric.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['addcomment'] = 'Adicionar comentário';
$string['addcriterion'] = 'Adicionar critério';
$string['addlevel'] = 'Adicionar nível';
$string['allowrecording'] = 'Permitir gravação pelo navegador';
$string['allowupload'] = 'Permitir envio de vídeo';
$string['audiofeedback'] = 'Feedback em áudio';
$string['authoredcomments'] = 'Comentários de avaliação criados';
$string['backtosubmissions'] = 'Voltar às entregas';
$string['completion:graded'] = 'O aluno deve receber uma avaliação';
$string['completion:minenable'] = 'O aluno deve receber no mínimo esta nota:';
$string['completion:submit'] = 'O aluno deve enviar a versão final do vídeo';
$string['completionrule:completiongraded'] = 'Receber uma avaliação';
$string['completionrule:completionmin'] = 'Atingir a nota mínima configurada';
$string['completionrule:completionminvalue'] = 'Receber nota mínima {$a}';
$string['completionrule:completionsubmit'] = 'Enviar a versão final do vídeo';
$string['criterion'] = 'Critério';
$string['currenttimestamp'] = 'Tempo atual';
$string['draftsaved'] = 'Rascunho salvo.';
$string['duedate'] = 'Data de entrega';
$string['duration'] = 'Duração';
$string['enablecamera'] = 'Ativar câmera';
$string['error:audiofilesize'] = 'O arquivo de feedback em áudio é muito grande.';
$string['error:audiotype'] = 'Formato de feedback em áudio não suportado.';
$string['error:duration'] = 'O vídeo ultrapassa a duração máxima configurada.';
$string['error:filesize'] = 'O vídeo ultrapassa o limite de upload da atividade.';
$string['error:incompleterubric'] = 'Selecione um nível em todos os critérios da rubrica antes de finalizar a avaliação.';
$string['error:nonnegative'] = 'O valor não pode ser negativo.';
$string['error:novideo'] = 'É necessário salvar um vídeo antes de enviar a versão final.';
$string['error:positivegrade'] = 'A nota máxima deve ser maior que zero.';
$string['error:submissionmethod'] = 'Habilite pelo menos uma forma de entrega.';
$string['error:upload'] = 'Não foi possível enviar o arquivo.';
$string['error:videotype'] = 'Formato de vídeo não suportado. Use MP4, WebM, MOV ou M4V.';
$string['event:submissioncreated'] = 'Entrega em vídeo criada';
$string['event:submissiongraded'] = 'Entrega em vídeo avaliada';
$string['event:submissionsubmitted'] = 'Entrega em vídeo enviada';
$string['event:temporalcommentcreated'] = 'Comentário temporal criado';
$string['feedback'] = 'Feedback';
$string['feedbacknotavailable'] = 'O feedback ainda não está disponível.';
$string['finalizegrade'] = 'Finalizar avaliação';
$string['finalscore'] = 'Nota final';
$string['gradingoptions'] = 'Opções de avaliação';
$string['gradingstudent'] = 'Avaliando {$a}';
$string['invalidsubmission'] = 'Entrega inválida.';
$string['level'] = 'Nível';
$string['levelgood'] = 'Bom';
$string['levelneedswork'] = 'Precisa melhorar';
$string['managerubric'] = 'Configurar rubrica';
$string['maxduration'] = 'Duração máxima da gravação (segundos)';
$string['maxduration_help'] = 'Duração máxima aceita para gravações feitas no navegador. Use 0 para não limitar.';
$string['maximumgrade'] = 'Nota máxima';
$string['modulename'] = 'Video Rubric';
$string['modulenameplural'] = 'Video Rubrics';
$string['nextstudent'] = 'Próximo aluno';
$string['norubric'] = 'Ainda não há uma rubrica configurada nesta atividade.';
$string['opensubmission'] = 'Abrir entrega';
$string['playbackspeed'] = 'Velocidade';
$string['pluginadministration'] = 'Administração do Video Rubric';
$string['pluginname'] = 'Video Rubric';
$string['points'] = 'Pontos';
$string['previousstudent'] = 'Aluno anterior';
$string['privacy:metadata:comment'] = 'Armazena comentários vinculados a momentos do vídeo.';
$string['privacy:metadata:comment:commenttext'] = 'Texto do feedback temporal.';
$string['privacy:metadata:comment:graderid'] = 'Usuário que escreveu o comentário.';
$string['privacy:metadata:comment:timeposition'] = 'Momento do vídeo associado ao comentário.';
$string['privacy:metadata:files'] = 'Vídeos enviados e feedbacks em áudio são armazenados pela File API do Moodle.';
$string['privacy:metadata:grade'] = 'Armazena avaliação e feedback.';
$string['privacy:metadata:grade:feedbacktext'] = 'Feedback textual escrito pelo avaliador.';
$string['privacy:metadata:grade:finalscore'] = 'Nota final calculada.';
$string['privacy:metadata:grade:graderid'] = 'Avaliador responsável pela correção.';
$string['privacy:metadata:grade:timegraded'] = 'Momento em que a avaliação foi finalizada.';
$string['privacy:metadata:submission'] = 'Armazena metadados das entregas em vídeo dos alunos.';
$string['privacy:metadata:submission:duration'] = 'Duração do vídeo.';
$string['privacy:metadata:submission:status'] = 'Estado de rascunho ou envio final.';
$string['privacy:metadata:submission:timesubmitted'] = 'Momento em que a versão final foi enviada.';
$string['privacy:metadata:submission:userid'] = 'Usuário proprietário da entrega.';
$string['privacy:metadata:timecreated'] = 'Data de criação.';
$string['privacy:metadata:timemodified'] = 'Data da última alteração.';
$string['recordaudio'] = 'Gravar áudio';
$string['recording'] = 'Gravando…';
$string['recordingnotallowed'] = 'A gravação pelo navegador está desabilitada nesta atividade.';
$string['recordvideo'] = 'Gravar vídeo';
$string['recordvideohelp'] = 'Grave usando câmera e microfone do navegador. Antes de salvar, você pode assistir e substituir a gravação.';
$string['reset:done'] = 'Dados de usuários do Video Rubric redefinidos';
$string['reset:grades'] = 'Excluir todas as avaliações e comentários do Video Rubric';
$string['reset:submissions'] = 'Excluir todas as entregas do Video Rubric';
$string['review'] = 'Corrigir';
$string['reviewsubmissions'] = 'Corrigir entregas';
$string['rubrichelp'] = 'Defina os critérios e níveis que ficarão permanentemente ao lado do vídeo durante a correção. Alterar uma rubrica que já foi utilizada pode exigir revisão das avaliações existentes.';
$string['rubricsaved'] = 'Rubrica salva.';
$string['saveaudio'] = 'Salvar áudio';
$string['saved'] = 'Salvo';
$string['savedraft'] = 'Salvar rascunho';
$string['saverecording'] = 'Salvar gravação como rascunho';
$string['saving'] = 'Salvando…';
$string['startrecording'] = 'Iniciar gravação';
$string['status:draft'] = 'Rascunho';
$string['status:graded'] = 'Avaliado';
$string['status:grading'] = 'Em avaliação';
$string['status:notsubmitted'] = 'Não enviado';
$string['status:submitted'] = 'Enviado';
$string['stoprecording'] = 'Parar gravação';
$string['student'] = 'Aluno';
$string['submission'] = 'Entrega em vídeo';
$string['submissionlocked'] = 'A entrega final está bloqueada.';
$string['submissionlockedinfo'] = 'Esta é a versão final enviada e o aluno não pode mais substituí-la.';
$string['submissionnotavailable'] = 'Esta entrega não está disponível.';
$string['submissionoptions'] = 'Opções de entrega';
$string['submissionsent'] = 'Versão final do vídeo enviada.';
$string['submitfinal'] = 'Enviar versão final';
$string['temporalcommentplaceholder'] = 'Comente o momento atual do vídeo…';
$string['temporalcomments'] = 'Comentários temporais';
$string['textfeedback'] = 'Feedback em texto';
$string['uploadnotallowed'] = 'O envio de vídeo está desabilitado nesta atividade.';
$string['uploadvideo'] = 'Enviar vídeo';
$string['useweights'] = 'Usar pesos nos critérios';
$string['videorubric:addinstance'] = 'Adicionar uma atividade Video Rubric';
$string['videorubric:grade'] = 'Avaliar entregas em vídeo';
$string['videorubric:managerubric'] = 'Configurar a rubrica da atividade';
$string['videorubric:submit'] = 'Enviar vídeos';
$string['videorubric:view'] = 'Visualizar a atividade Video Rubric';
$string['videorubric:viewallgroups'] = 'Visualizar entregas de todos os grupos';
$string['videorubricname'] = 'Nome do Video Rubric';
$string['weight'] = 'Peso';
