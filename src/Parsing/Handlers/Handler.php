<?php declare(strict_types=1);

namespace Sassnowski\Roach\Parsing\Handlers;

use Sassnowski\Roach\Support\ConfigurableInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class Handler implements ConfigurableInterface
{
    private OptionsResolver $resolver;

    public function __construct(protected array $options = [])
    {
        $this->resolver = new OptionsResolver();

        $this->resolver->setDefaults($options);
    }

    public function configure(array $options): void
    {
        $this->options = $this->resolver->resolve($options);
    }
}
