<?php
// includes/helpers.php

if (!function_exists('time_elapsed_string')) {
    /**
     * Converte uma data/hora em uma string de tempo relativo (ex: "5 min atrás").
     * Retorna a data original formatada em caso de erro.
     * Tenta usar 'America/Sao_Paulo', mas usa 'UTC' como fallback se o fuso não for válido.
     * Adicionado tratamento para '0000-00-00 00:00:00'.
     *
     * @param string $datetime String da data/hora (formato compatível com DateTime, ex: 'Y-m-d H:i:s').
     * @param bool $full Se true, mostra mais detalhes (ex: "1 ano, 2 meses atrás").
     * @return string String do tempo relativo ou data formatada/mensagem de erro.
     */
    function time_elapsed_string($datetime, $full = false): string {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
            return 'Data inválida';
        }

        $timezoneIdentifier = 'America/Sao_Paulo';
        try {
            $tz = new DateTimeZone($timezoneIdentifier);
        } catch (Exception $e) {
            error_log("Fuso horário '$timezoneIdentifier' inválido no servidor, usando UTC como fallback. Erro: " . $e->getMessage());
            $tz = new DateTimeZone('UTC');
        }

        try {
            $now = new DateTime('now', $tz);
            $ago = new DateTime($datetime, $tz);
            $diff = $now->diff($ago);

            // NÃO criamos mais $diff->w aqui

            $string = array(
                'y' => ['ano', 'anos'],
                'm' => ['mês', 'meses'],
                'w' => ['semana', 'semanas'], // Mantém a chave 'w' para o loop
                'd' => ['dia', 'dias'],
                'h' => ['hora', 'horas'],
                'i' => ['minuto', 'minutos'],
                's' => ['segundo', 'segundos'],
            );
            $result = [];

            // Variáveis temporárias para dias e semanas
            $total_days = $diff->d; // Pega o número de dias restantes após meses/anos
            $weeks = 0;

            foreach ($string as $key => $value) {
                $interval_value = 0; // Valor para a unidade atual

                if ($key === 'w') {
                    // Calcula semanas a partir dos dias restantes
                    if ($total_days >= 7) {
                        $weeks = floor($total_days / 7);
                        $interval_value = $weeks;
                        $total_days -= $weeks * 7; // Subtrai os dias que viraram semanas
                    }
                } elseif ($key === 'd') {
                     // Usa os dias restantes após o cálculo das semanas
                    $interval_value = $total_days;
                } elseif (property_exists($diff, $key)) {
                    // Pega o valor diretamente do objeto DateInterval para y, m, h, i, s
                    $interval_value = $diff->$key;
                }

                // Se o valor calculado para a unidade for maior que zero
                if ($interval_value > 0) {
                    // Adiciona a string formatada (ex: "2 dias") ao resultado
                    $result[$key] = $interval_value . ' ' . ($interval_value > 1 ? $value[1] : $value[0]); // Usa plural ou singular
                }
            }

            if (!$full) {
                $result = array_slice($result, 0, 1);
            }

            return $result ? implode(', ', $result) . ' atrás' : 'agora';

        } catch (Exception $e) {
            error_log("Erro em time_elapsed_string ao processar data '$datetime': " . $e->getMessage());
            $timestamp = strtotime($datetime);
            if ($timestamp !== false) {
                 return date('d/m/Y H:i', $timestamp);
            } else {
                 return 'Data Inválida (' . htmlspecialchars(substr($datetime, 0, 25)) . ')';
            }
        }
    }
}
?>