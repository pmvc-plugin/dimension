<?php

namespace PMVC\PlugIn\dimension;

use DomainException; 

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\Encryption';

class Encryption
{

    const PLAIN_TEXT_EXT='.plaintext';
    private $_folder;
    private $_encryptor;

    public function __invoke($folder, $key)
    {
        $realPath = \PMVC\realPath($folder);
        if (empty($realPath)) {
            throw new DomainException('Dimensions settings folder not exists. ['.$folder.']');
        } else {
            $this->_folder = $folder;
        }
        $this->_encryptor = \PMVC\plug(
            'simple_encryptor',
            ['key'=>$key]
        );
        return $this;
    }

    public function encode()
    {
        $plainTextFiles = glob(
            $this->_folder.
            '/.*.pw'.self::PLAIN_TEXT_EXT
        );
        if (empty($plainTextFiles)) {
            throw new DomainException('Not found plain text files. ['.$this->_folder.']');
        }
        foreach ($plainTextFiles as $f) {
            $text = file_get_contents($f);
            $newName = substr($f, 0, strrpos($f, self::PLAIN_TEXT_EXT));
            $encodeText = $this->
                _encryptor->
                encode($text);
            if (!empty($encodeText)) {
              file_put_contents($newName, $encodeText);
            }
        }
    }

    public function decode()
    {
        $secretFiles = glob(
            $this->_folder.
            '/.*.pw'
        );
        if (empty($secretFiles)) {
            throw new DomainException('Not found secret files. ['.$this->_folder.']');
        }
        foreach ($secretFiles as $f) {
            $text = file_get_contents($f);
            $newName = $f.self::PLAIN_TEXT_EXT;
            $decodeText = $this->
                _encryptor->
                decode($text);
            if (!empty($decodeText)) {
              file_put_contents($newName, $decodeText);
            }
        }
    }
}
