<?php declare(strict_types=1);

namespace Sassnowski\Roach\ItemPipeline;

use Sassnowski\Roach\ItemPipeline\Processors\ItemProcessorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class ItemProcessor implements ItemProcessorInterface
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
