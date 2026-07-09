<?php

namespace App\Exceptions;

use Exception;

// Se lanza cuando una foto no se pudo escribir en el disco (store() devolvió false).
class AlmacenamientoException extends Exception {}
