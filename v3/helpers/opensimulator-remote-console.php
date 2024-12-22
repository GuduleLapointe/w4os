<?php
class W4OS_RestAdmin
{
    function __construct($sURL, $sPort, $pass)
    {
        $this->simulatorURL = $sURL;
        $this->simulatorPort = $sPort;
        $this->password = $pass;
    }

    function SendCommand($command, $params=array())
    {
        $paramsNames = array_keys($params);
        $paramsValues = array_values($params);

        $xml = '
    <methodCall>
      <methodName>'.htmlspecialchars($command).'</methodName>
      <params>
        <param>
          <value>
            <struct>
              <member>
                <name>password</name>
                <value><string>'.htmlspecialchars($this->password).'</string></value>
              </member>';
        if (count($params) != 0) {
            for ($p = 0; $p < count($params); $p++)
            {
                  $xml .= '<member><name>'.htmlspecialchars($paramsNames[$p]).'</name>';
                  $xml .= is_int($paramsValues[$p]) ? '<value><int>'.$paramsValues[$p].'</int></value></member>' : '<value><string>'.htmlspecialchars($paramsValues[$p]).'</string></value></member>';
            }
        }
            $xml .= '</struct>
          </value>
        </param>
      </params>
    </methodCall>';

        $host = $this->simulatorURL;
        $port = $this->simulatorPort;
        $timeout = 5;
        error_reporting(0);
        $fp = fsockopen($host, $port, $errno, $errstr, $timeout);

        if (!$fp) {
            return false;
        }

        else
        {
            fputs($fp, "POST / HTTP/1.1\r\n");
            fputs($fp, "Host: $host\r\n");
            fputs($fp, "Content-type: text/xml\r\n");
            fputs($fp, "Content-length: ". strlen($xml) ."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $xml);
            $res = "";

            while(!feof($fp))
            {
                $res .= fgets($fp, 128);
            }

            fclose($fp);
            $response = substr($res, strpos($res, "\r\n\r\n"));
            $result = array();

            if (preg_match_all('#<name>(.+)</name><value><(string|int|boolean|i4)>(.*)</\2></value>#U', $response, $regs, PREG_SET_ORDER)) {
                foreach($regs as $key => $val)
                {
                    $result[$val[1]] = $val[3];
                }
            }
            return $result;
        }
    }
}
