<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class DocumentatieController extends Controller
{
    /**
     * Publicly served documentation pages.
     *
     * Files live in doc/ (public) — developer docs live in doc/dev/ (never served).
     * Only add entries here for files that should be accessible via the web.
     * Pages with a 'role' key are restricted to authenticated users with that role.
     */
    private const PAGES = [
        'handleiding' => [
            'bestand' => 'gebruikershandleiding.md',
            'titel' => 'Gebruikershandleiding',
            'beschrijving' => 'Uitleg over inloggen, aanmelden voor rondes en het bekijken van standen en uitslagen.',
        ],
        'puntensysteem' => [
            'bestand' => 'keizer-puntensysteem.md',
            'titel' => 'Keizer Puntensysteem',
            'beschrijving' => 'Hoe het Keizer puntensysteem werkt: rangwaarden, puntentoekenning en klassementsberekening.',
        ],
        'indelingsalgoritmen' => [
            'bestand' => 'indelingsalgoritmen.md',
            'titel' => 'Indelingsalgoritmen',
            'beschrijving' => 'Uitleg over de Swiss- en Keizer-indelingssystemen die gebruikt worden voor de partij-indeling.',
        ],
        'wedstrijdleider' => [
            'bestand' => 'wedstrijdleider-handleiding.md',
            'titel' => 'Wedstrijdleider Handleiding',
            'beschrijving' => 'Beheer van seizoenen, spelers, rondes, indelingen en resultaten.',
            'role' => 'wedstrijdleider',
        ],
    ];

    public function index(): View
    {
        $paginas = array_filter(self::PAGES, function (array $pagina) {
            if (! isset($pagina['role'])) {
                return true;
            }

            return auth()->check() && auth()->user()->role === $pagina['role'];
        });

        return view('documentatie.index', [
            'paginas' => $paginas,
        ]);
    }

    public function show(string $slug): View
    {
        if (! isset(self::PAGES[$slug])) {
            abort(404);
        }

        $pagina = self::PAGES[$slug];

        if (isset($pagina['role'])) {
            if (! auth()->check() || auth()->user()->role !== $pagina['role']) {
                abort(403);
            }
        }

        $pad = base_path('doc/'.$pagina['bestand']);

        if (! file_exists($pad)) {
            abort(404);
        }

        $markdown = file_get_contents($pad);

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'insert' => 'none',
                'id_prefix' => '',
                'fragment_prefix' => '',
                'apply_id_to_heading' => true,
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new HeadingPermalinkExtension);

        $converter = new MarkdownConverter($environment);
        $html = $converter->convert($markdown)->getContent();

        return view('documentatie.show', [
            'titel' => $pagina['titel'],
            'html' => $html,
        ]);
    }
}
