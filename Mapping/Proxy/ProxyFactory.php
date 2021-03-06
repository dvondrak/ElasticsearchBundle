<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Mapping\Proxy;

use Doctrine\Common\Inflector\Inflector;

/**
 * Generates proxy classes for documents.
 */
class ProxyFactory
{
    /**
     * Returns proxy namespace.
     *
     * @param \ReflectionClass $reflectionClass Original class reflection.
     * @param bool             $withName        Includes class name also.
     *
     * @return string
     */
    public static function getProxyNamespace(\ReflectionClass $reflectionClass, $withName = true)
    {
        $namespace = $reflectionClass->getNamespaceName() . '\\_Proxy';

        if ($withName) {
            $namespace .= '\\' . $reflectionClass->getShortName();
        }

        return $namespace;
    }

    /**
     * Generates proxy class with setters and getters by reflection.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return string
     */
    public static function generate(\ReflectionClass $reflectionClass)
    {
        $code = self::getHeader(
            [
                'namespace' => self::getProxyNamespace($reflectionClass, false),
                'class' => $reflectionClass->getShortName(),
                'base' => $reflectionClass->getName(),
            ]
        );
        $code .= self::getProxyContent();
        $code .= self::generateSettersAndGetters($reflectionClass);
        $code .= self::getFooter();

        return $code;
    }

    /**
     * Generates proxy related content.
     *
     * @return string
     */
    private static function getProxyContent()
    {
        return <<<EOF

    private \$__isInitialized = false;

    public function __isInitialized()
    {
        return \$this->__isInitialized;
    }
    public function __setInitialized(\$initialized)
    {
        \$this->__isInitialized = \$initialized;
    }

EOF;
    }

    /**
     * Generates missing getters and setters.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return string
     */
    private static function generateSettersAndGetters(\ReflectionClass $reflectionClass)
    {
        $code = '';

        /** @var \ReflectionProperty $property */
        foreach (self::getProperties($reflectionClass) as $propertyName) {
            $methodName = ucfirst(Inflector::classify($propertyName));
            if (!$reflectionClass->hasMethod("get{$methodName}")) {
                $code .= self::getMethod(
                    [
                        'name' => "get{$methodName}",
                        'content' => "return \$this->{$propertyName};",
                    ]
                );
            }

            if (!$reflectionClass->hasMethod("set{$methodName}")) {
                $code .= self::getMethod(
                    [
                        'name' => "set{$methodName}",
                        'content' => "\$this->{$propertyName} = \${$propertyName};",
                        'params' => "\$$propertyName",
                    ]
                );
            }
        }

        return $code;
    }

    /**
     * Returns all document properties.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private static function getProperties(\ReflectionClass $reflectionClass)
    {
        $out = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $out[] = $property->getName();
        }

        $parent = $reflectionClass->getParentClass();
        if ($parent !== false) {
            $out = array_unique(array_merge($out, self::getProperties($parent)));
        }

        return $out;
    }

    /**
     * Gives php class file header.
     *
     * @param array $options
     *
     * @return string
     */
    private static function getHeader(array $options)
    {
        extract($options);

        return <<<EOF
<?php

namespace $namespace;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY ELASTICSEARCH PROXY FACTORY.
 */
class $class extends \\$base implements \ONGR\ElasticsearchBundle\Mapping\Proxy\ProxyInterface
{

EOF;
    }

    /**
     * Generates method.
     *
     * @param array $options
     *
     * @return string
     */
    private static function getMethod(array $options)
    {
        extract($options);
        $out = <<<EOF
    public function $name(
EOF;

        if (isset($params)) {
            $out .= $params;
        }
        $out .= ")\n";
        $out .= <<<EOF
    {
        $content
    }

EOF;

        return $out;
    }

    /**
     * Gives php class file footer.
     *
     * @return string
     */
    private static function getFooter()
    {
        return "}\n";
    }
}
