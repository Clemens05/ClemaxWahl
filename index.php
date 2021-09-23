<?php

define('MAINPATH', file_get_contents('mainpath'));

$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

define('URL_SCHEME', parse_url($url, PHP_URL_SCHEME));
define('URL_HOST', parse_url($url, PHP_URL_HOST));
define('URL_PORT', parse_url($url, PHP_URL_PORT));
define('URL_QUERY', parse_url($url, PHP_URL_QUERY));
define('URL_FRAGMENT', parse_url($url, PHP_URL_FRAGMENT));

$url_path = parse_url($url, PHP_URL_PATH);
if (substr($url_path, -1) == '/') {
    define('URL_PATH', substr($url_path, 0, -1));
} else {
    define('URL_PATH', $url_path);
}

function echoFooter() {
    echo '
        <title>ClemaxWahl</title>
        <hr>
        <a href="' . MAINPATH . '/prognose/raw">JSON Daten</a>
         | 
        <a href="' . MAINPATH . '/prognose/last_updated">Zuletzt erneuert</a>
    ';
}

function parse_url_path() {
    switch (URL_PATH) {
        case MAINPATH . '/prognose':
            return [
                'content' => prognose()
            ];
            break;
    
        case MAINPATH . '/prognose/raw':
            return [
                'json' => file_get_contents('wahlprognose.json')
            ];
            break;
    
        case MAINPATH . '/prognose/last_updated':
            return [
                'content' => json_decode(file_get_contents('wahlprognose.json'), true)['last_updated']
            ];
            break;

        case MAINPATH:
            header('Location: prognose');
            exit;
        
        default:
            return [
                'unified_error_id' => 10000,
                'error' => 'URL_PATH_NOT_FOUND',
                'message' => 'URL path not found'
            ];
            break;
    }
}

class Prognose {
    private $html;
    private $content;

    function __construct() {
        $this->addText('Prognose zur Bundestagswahl 2021', 'h1');

        $this->content = json_decode(file_get_contents('wahlprognose.json'), true);
        
        $this->addPollValues();

        $this->addPossibleCoalitions();

        $this->addText('Zuletzt erneuert: ' . $this->content['last_updated'], 'alert');
    }

    private function addPollValues() {
        $this->addText('Aktuelle Umfragewerte', 'h2');

        $this->addHTML('<table border=1>');
        $party_names_row = '<tr>';
        $forecast_row = '<tr>';
        for ($i=0; $i < count($this->content['forecast']); $i++) {
            $party_names_row .= '<th>' . $this->content['partys'][$i]['shortname'] . '</th>';
            $forecast_row .= '<td>' . $this->content['forecast'][$this->content['partys'][$i]['shortcut']]['bundestagswahl-2021'] . '</td>';
        }
        $party_names_row .= '</tr>';
        $forecast_row .= '</tr>';

        $this->addHTML($party_names_row . $forecast_row . '</table>');
    }

    private function addPossibleCoalitions() {
        $this->addText('Mögliche Koalitionen unter der Berücksichtigung der Umfragewerte', 'h2');

        $table = '<table border=1>';

        for ($i=0; $i < count($this->content['partys']); $i++) { 
            if ($this->content['partys'][$i]['shortcut'] != 'other') {
                if ($this->content['forecast'][$this->content['partys'][$i]['shortcut']]['bundestagswahl-2021'] < 5) {
                    $forecast = $this->content['forecast'];

                    $wahl = 'bundestagswahl-2021';
                    $cdu = $forecast['cdu'][$wahl];
                    $spd = $forecast['spd'][$wahl];
                    $fdp = $forecast['fdp'][$wahl];
                    $gruene = $forecast['gruene'][$wahl];
                    $linke = $forecast['linke'][$wahl];
                    $afd = $forecast['afd'][$wahl];
                    $fw = $forecast['fw'][$wahl];

                    $table .= '
                        <tr>
                            <td>
                                CDU
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($cdu) . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                SPD
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($spd) . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Große Koalition (CDU + SPD)
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($cdu + $spd) . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Jamaika-Koalition (CDU + FDP + GRÜNE)
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($cdu + $fdp + $gruene) . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Ampelkoalition (SPD + FDP + GRÜNE)
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($spd + $fdp + $gruene) . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Schwarz-Rot-Grün (CDU + SPD + GRÜNE)
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($cdu + $spd + $gruene) . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Schwarz-Rot-Gelb (CDU + SPD + FDP)
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($cdu + $spd + $fdp) . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Rot-Rot-Grün (SPD + GRÜNE + LINKE)
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($spd + $gruene + $linke) . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Rot-Grün (SPD + GRÜNE)
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($spd + $gruene) . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Schwarz-Grün (CDU + GRÜNE)
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($cdu + $gruene) . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Schwarz-Gelb (CDU + FDP)
                            </td>
                            <td>
                                '. $this->isCoalitionPollible($cdu + $fdp) . '
                            </td>
                        </tr>
                    ';
                    
                    if ($forecast['cdu'] > 50) {
                        
                    } else if ($forecast['spd'] > 50) {

                    } else if ($forecast['cdu'] + $forecast['spd'] > 50) {
                        
                    }
                }
            }
        }

        $table .= '</table>';

        $this->addHTML($table);
        $this->addText('Da jede Partei ausdrücklich verneint hat, mit der AfD koalieren zu wollen, ist diese in dieser Darstellung nicht aufgelistet.<br>Außerdem wird die Partei Freie Wähler vermutlich die 5%-Hürde nicht erreichen, weshalb sie ebenfalls nicht aufgelistet ist.', 'information');
    }

    private function isCoalitionPollible($values) {
        if ($values > 50) {
            return "Ja";
        } else {
            return "Nein";
        }
    }

    public function addHTML(String $content) {
        $this->html .= $content;
    }

    /**
     * Adds a Heading or Text to HTML
     * @param $content The content of the text or heading
     * @param $type The type of the text or heading (e.g. h1, h3, p, etc.)
     * 
     * List of supported types:
     *  -   h1, h2, h3, h4, h5, h6
     *  -   p
     *  -   code (for <code>)
     *  -   danger (for red, bold text)
     *  -   alert (for dark yellow(#9B870C), bold text)
     *  -   information (for blue, bold text)
     */
    public function addText(string $content, string $type = 'p') {
        switch ($type) {
            case 'h1':
                $this->html .= '<h1>' . $content . '</h1>';
                break;
            
            case 'h2':
                $this->html .= '<h2>' . $content . '</h2>';
                break;
            
            case 'h3':
                $this->html .= '<h3>' . $content . '</h3>';
                break;
            
            case 'h4':
                $this->html .= '<h4>' . $content . '</h4>';
                break;
            
            case 'h5':
                $this->html .= '<h5>' . $content . '</h5>';
                break;
            
            case 'h6':
                $this->html .= '<h6>' . $content . '</h6>';
                break;
            
            case 'p':
                $this->html .= '<p>' . $content . '</p>';
                break;
            
            case 'code':
                $this->html .= '<code>' . $content . '</code>';
                break;
            
            case 'danger':
                $this->html .= '<p style="color: red"><b>' . $content . '</b></p>';
                break;

            case 'alert':
                $this->html .= '<p style="color: #9B870C"><b>' . $content . '</b></p>';
                break;
            
            case 'information':
                $this->html .= '<p style="color: blue"><b>' . $content . '</b></p>';
                break;

            default:
                $this->html .= '<p>' . $content . '</p>';
                break;
        }
    }

    public function getHTML() {
        return $this->html;
    }
}

function prognose() {
    $prognose = new Prognose();

    return $prognose->getHTML();
}

$content = parse_url_path();

if (isset($content['content'])) {
    echo $content['content'];
    if (URL_PATH != MAINPATH . '/prognose/last_updated') echoFooter();
} else if (isset($content['error'])) {
    header('Content-Type: application/json');
    echo json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
} else if (isset($content['json'])) {
    header('Content-Type: application/json');
    echo $content['json'];
}