<?php

namespace Pagerfanta\View;

use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\Template\TemplateInterface;

abstract class TemplateView extends View
{
    /**
     * @var TemplateInterface
     */
    private $template;

    public function __construct(TemplateInterface $template = null)
    {
        $this->template = $template ?: $this->createDefaultTemplate();
    }

    /**
     * @return TemplateInterface
     */
    abstract protected function createDefaultTemplate();

    /**
     * @throws InvalidArgumentException if the $routeGenerator is not a callable
     */
    public function render(Pagerfanta $pagerfanta, $routeGenerator, array $options = [])
    {
        if (!is_callable($routeGenerator)) {
            throw new InvalidArgumentException(sprintf('The $routeGenerator argument of %s() must be a callable, a %s was given.', __METHOD__, gettype($routeGenerator)));
        }

        $this->initializePagerfanta($pagerfanta);
        $this->initializeOptions($options);

        $this->configureTemplate($routeGenerator, $options);

        return $this->generate();
    }

    private function configureTemplate(callable $routeGenerator, array $options): void
    {
        $this->template->setRouteGenerator($routeGenerator);
        $this->template->setOptions($options);
    }

    private function generate(): string
    {
        return $this->generateContainer($this->generatePages());
    }

    private function generateContainer(string $pages): string
    {
        return str_replace('%pages%', $pages, $this->template->container());
    }

    private function generatePages(): string
    {
        $this->calculateStartAndEndPage();

        return $this->previous().
               $this->first().
               $this->secondIfStartIs3().
               $this->dotsIfStartIsOver3().
               $this->pages().
               $this->dotsIfEndIsUnder3ToLast().
               $this->secondToLastIfEndIs3ToLast().
               $this->last().
               $this->next();
    }

    private function previous(): string
    {
        if ($this->pagerfanta->hasPreviousPage()) {
            return $this->template->previousEnabled($this->pagerfanta->getPreviousPage());
        }

        return $this->template->previousDisabled();
    }

    private function first(): string
    {
        if ($this->startPage > 1) {
            return $this->template->first();
        }

        return '';
    }

    private function secondIfStartIs3(): string
    {
        if (3 === $this->startPage) {
            return $this->template->page(2);
        }

        return '';
    }

    private function dotsIfStartIsOver3(): string
    {
        if ($this->startPage > 3) {
            return $this->template->separator();
        }

        return '';
    }

    private function pages(): string
    {
        $pages = '';

        foreach (range($this->startPage, $this->endPage) as $page) {
            $pages .= $this->page($page);
        }

        return $pages;
    }

    private function page(int $page): string
    {
        if ($page === $this->currentPage) {
            return $this->template->current($page);
        }

        return $this->template->page($page);
    }

    private function dotsIfEndIsUnder3ToLast(): string
    {
        if ($this->endPage < $this->toLast(3)) {
            return $this->template->separator();
        }

        return '';
    }

    private function secondToLastIfEndIs3ToLast(): string
    {
        if ($this->endPage == $this->toLast(3)) {
            return $this->template->page($this->toLast(2));
        }

        return '';
    }

    private function last(): string
    {
        if ($this->pagerfanta->getNbPages() > $this->endPage) {
            return $this->template->last($this->pagerfanta->getNbPages());
        }

        return '';
    }

    private function next(): string
    {
        if ($this->pagerfanta->hasNextPage()) {
            return $this->template->nextEnabled($this->pagerfanta->getNextPage());
        }

        return $this->template->nextDisabled();
    }

    public function getName()
    {
        return 'default';
    }
}

/*

CSS:

.pagerfanta {
}

.pagerfanta a,
.pagerfanta span {
    display: inline-block;
    border: 1px solid blue;
    color: blue;
    margin-right: .2em;
    padding: .25em .35em;
}

.pagerfanta a {
    text-decoration: none;
}

.pagerfanta a:hover {
    background: #ccf;
}

.pagerfanta .dots {
    border-width: 0;
}

.pagerfanta .current {
    background: #ccf;
    font-weight: bold;
}

.pagerfanta .disabled {
    border-color: #ccf;
    color: #ccf;
}

COLORS:

.pagerfanta a,
.pagerfanta span {
    border-color: blue;
    color: blue;
}

.pagerfanta a:hover {
    background: #ccf;
}

.pagerfanta .current {
    background: #ccf;
}

.pagerfanta .disabled {
    border-color: #ccf;
    color: #cf;
}

*/
