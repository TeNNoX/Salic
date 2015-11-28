<?php

namespace salic;


class Parser
{

    public static function parseFile($filename, $vals = array())
    {
        $parser = new Parser(file_get_contents($filename), $vals);
        $parser->parse();
        return $parser->result;
    }

    var $src;
    var $result;
    var $values;

    public function __construct($src, &$values = array(), &$result = "")
    {
        $this->src = $src;
        $this->result = $result;
        $this->values = $values;
    }

    /**
     * parse the statements in [a part of] the template
     *
     * @throws ParseException
     */
    private function parse()
    {
        $index = 0;

        while (($startpos = strpos($this->src, '{{', $index)) !== false) { //TODO: escapable '{{'
            // add the stuff before this point to the result
            $this->result .= substr($this->src, $index, $startpos - $index);

            if ($this->src{$startpos + 2} == '*') { // check if it starts with a '*' = comment
                $endpos = strpos($this->src, "*}}", $startpos + 3);
                if ($endpos === false) {
                    throw new ParseException("comment not closed (startindex=$startpos)");
                }
                echo "Just a comment...<br>" . PHP_EOL;

            } else { // Not a comment -> parse it
                $endpos = strpos($this->src, "}}", $startpos + 2);
                if ($endpos === false) {
                    throw new ParseException("statement not closed (startindex=$startpos)");
                }

                // extract actual statement
                $statement = substr($this->src, $startpos + 2, $endpos - $startpos - 2);

                $endpos = $this->handle_statement($statement, $endpos + 2);
            }

            if ($endpos + 2 <= $index)
                throw new ShouldNotHappenException("endpos invalid: $endpos (index=$index)");

            // move up the current index to after this parsed block
            $index = $endpos + 2;
        }

        // add all the rest to the result
        $this->result .= substr($this->src, $index);
    }

    private function handle_statement($statement, $blockstart)
    {
        echo "Handling {{ $statement }} -> ";
        $blockend = $blockstart;

        if (preg_match('/^(.*):(.+)$/', $statement, $matches)) { // check for a regular {{ [type] : more }} statement
            $type = trim(strtolower($matches[1]));
            $opts = trim($matches[2]);
            if (!$type) {
                $type = 'val';
            }
            echo "type=$type, more=$opts<br>" . PHP_EOL;

            if ($type == 'val') {
                $this->result .= $this->get_val(trim($opts));
            } else if ($type == 'foreach') {
                $blockend = $this->handle_foreach($opts, $blockstart);
            }
        } else {
            throw new ParseException("Invalid Statement: '$statement'");
        }
        return $blockend; // return new block end
    }

    private function handle_foreach($opts, $blockstart)
    {
        if (!preg_match('/^(.+)=([^<}]+)(<?)$/', $opts, $matches)) { // parse the value names
            throw new ParseException("Foreach options invalid: '$opts' (should be 'VALNAME = ITEMNAME') ");
        }
        $listname = trim($matches[1]);
        $itemname = trim($matches[2]);

        $items = $this->get_val($listname);
        if (!is_array($items)) {
            throw new \UnexpectedValueException("(foreach) value not array: " . var_export($items, true));
        }

        // get block end
        if (!preg_match('/{{\s*(>?)\s*\/for\s*}}/i', substr($this->src, $blockstart), $matches, PREG_OFFSET_CAPTURE)) {
            throw new ParseException("foreach not closed (startindex=$blockstart)");
        }
        $blockend = $blockstart + $matches[0][1]; // add blockstart, because it was substracted in preg_match

        foreach($items as $item) {
            $vals = $this->values;
            $vals[$itemname] = $item;
            echo "< Subparser :<br>".PHP_EOL;
            $sub = new Parser(substr($this->src, $blockstart, $blockend-$blockstart), $vals, $this->result);
            $sub->parse();
            echo "> Subparser<br>".PHP_EOL;
        }

        echo "match=".var_export($matches, true)."<br>";
        return $blockend + strlen($matches[0][0]);
    }

    private function get_val($name)
    {
        if (!array_key_exists($name, $this->values)) {
            var_export($this->values);
            throw new ParseException("Value '$name' not found");
        }
        return $this->values[$name];
    }
}