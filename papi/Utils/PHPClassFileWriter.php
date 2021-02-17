<?php
declare(strict_types=1);

namespace papi\Utils;

use JetBrains\PhpStorm\Pure;

class PHPClassFileWriter
{
    private string $name;

    private string $dir;

    private string $namespace;

    private ?string $extends;

    private array $variables = [];

    private array $functions = [];

    private array $imports = [];

    public function __construct(
        string $name,
        string $namespace,
        string $dir,
        ?string $extends = null
    ) {
        $this->name = $name;
        $this->namespace = $namespace;
        $this->dir = $dir;
        if ($extends) {
            $this->extends = "extends $extends";
        }
    }

    public function write(): void
    {
        file_put_contents($this->dir."/$this->name.php", $this->getTemplate());
    }

    private function getTemplate(): string
    {
        $vars = $this->getVars();
        $functions = $this->getFunctions();
        $imports = $this->getImports();

        return "<?php
declare(strict_types=1);

namespace $this->namespace;

$imports

class $this->name $this->extends
{
$vars 
$functions
}";
    }

    #[Pure] private function getVars(): string
    {
        $template = '';
        foreach ($this->variables as $key => $variable) {
            $template .= "    $variable;\n";
            if (array_key_last($this->variables) !== $key) {
                $template .= "\n";
            }
        }

        return $template;
    }

    #[Pure] private function getFunctions(): string
    {
        $template = '';
        foreach ($this->functions as $key => $function) {
            $template .= $function;
            if (array_key_last($this->functions) !== $key) {
                $template .= "\n\n";
            }
        }

        return $template;
    }

    #[Pure] private function getImports(): string
    {
        $template = "";
        foreach ($this->imports as $key => $import) {
            $template .= "use $import;";
            if (array_key_last($this->imports) !== $key) {
                $template .= "\n";
            }
        }

        return $template;
    }

    public function addFunction(
        string $access,
        string $returnType,
        string $name,
        string $content,
        array $args = []
    ): void {
        $text = "    $access function $name(";
        foreach ($args as $key => $arg) {
            $text .= $arg;
            if (array_key_last($args) !== $key) {
                $text .= ',';
            }
        }
        $text .= "): $returnType
    {
        $content
    }";
        $this->functions[] = $text;
    }

    public function addVariable(string $access, string $type, string $name, $defaultValue = null): void
    {
        $var = "$access $type $$name";
        if ($defaultValue) {
            if (is_string($defaultValue)) {
                $defaultValue = "'$defaultValue'";
            }
            $var .= " = $defaultValue";
        }
        $this->variables[] = $var;
    }

    public function addImport(string $path): void
    {
        $this->imports[] = $path;
    }
}