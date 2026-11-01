@extends('layouts.admin.main')
@section('title', 'Organization Chart')
@section('subtitle', 'HRM · Built from designations and who they report to')

@push('styles')
<style>
    .org-wrap { overflow: auto; padding: 1rem 0 2rem; }
    .org, .org ul { display: flex; justify-content: center; padding-top: 22px; position: relative; margin: 0; padding-left: 0; list-style: none; }
    .org { padding-top: 0; }
    .org li { position: relative; padding: 22px 10px 0; text-align: center; display: flex; flex-direction: column; align-items: center; }
    .org li::before, .org li::after { content: ''; position: absolute; top: 0; right: 50%; width: 50%; height: 22px; border-top: 2px solid #c7d2fe; }
    .org li::after { right: auto; left: 50%; border-left: 2px solid #c7d2fe; }
    .org li:only-child::before, .org li:only-child::after { display: none; }
    .org li:only-child { padding-top: 0; }
    .org li:first-child::before, .org li:last-child::after { border: 0; }
    .org li:last-child::before { border-right: 2px solid #c7d2fe; border-radius: 0 8px 0 0; }
    .org li:first-child::after { border-radius: 8px 0 0 0; }
    .org ul::before { content: ''; position: absolute; top: 0; left: 50%; height: 22px; border-left: 2px solid #c7d2fe; }
    .org > li { padding-top: 0; }
    .org-node { background: var(--app-surface); border: 1px solid var(--app-border); border-radius: 14px; box-shadow: var(--app-shadow);
        min-width: 210px; max-width: 250px; overflow: hidden; }
    .org-node__head { background: var(--app-btn-grad); color: #fff; padding: .55rem .8rem; }
    .org-node__head strong { display: block; font-size: .85rem; }
    .org-node__head small { opacity: .75; font-size: .72rem; }
    .org-node__people { padding: .55rem; display: grid; gap: .35rem; }
    .org-person { display: flex; align-items: center; gap: .5rem; text-align: left; font-size: .8rem; text-decoration: none; color: var(--app-text);
        padding: .25rem .35rem; border-radius: 8px; }
    a.org-person:hover { background: var(--app-primary-soft); }
    .org-person .app-avatar { width: 28px; height: 28px; font-size: .68rem; }
    .org-node--person { display: flex; flex-direction: column; align-items: center; gap: .15rem; padding: .9rem .8rem .75rem; min-width: 170px; max-width: 200px;
        text-decoration: none; color: var(--app-text); border-top: 3px solid #6366f1; transition: transform .15s, box-shadow .15s; }
    a.org-node--person:hover { transform: translateY(-2px); box-shadow: var(--app-shadow-hover); color: var(--app-text); }
    .org-node--person .app-avatar { width: 42px; height: 42px; font-size: .85rem; margin-bottom: .3rem; }
    .org-node--person strong { font-size: .85rem; line-height: 1.2; }
    .org-node--person small { font-size: .74rem; color: var(--app-text-muted); line-height: 1.2; }
    .org-node--person .org-dept { font-size: .68rem; opacity: .8; }
    .org-count { margin-top: .35rem; font-size: .65rem; font-weight: 600; color: #4f46e5; background: rgba(99,102,241,.1); padding: .1rem .5rem; border-radius: 99px; }
    .org-vacant { font-size: .75rem; color: var(--app-text-muted); font-style: italic; padding: .25rem; }
</style>
@endpush

@php
    $canOpen = auth()->user()->can('hrm.view_employees');
    $renderPeople = function ($nodes) use (&$renderPeople, &$people, $canOpen) {
        $html = '';
        foreach ($nodes as $p) {
            $kids = $people->get($p->id, collect());
            $tag = $canOpen ? 'a href="'.route('hrm.employees.show', $p).'"' : 'div';
            $html .= '<li><'.$tag.' class="org-node org-node--person">'
                .'<span class="app-avatar">'.e(\App\Support\Hrm\Hrm::initials($p->name)).'</span>'
                .'<strong>'.e($p->name).'</strong>'
                .'<small>'.e($p->designation->name ?? '—').'</small>'
                .'<small class="org-dept">'.e($p->department->name ?? '').'</small>'
                .($kids->isNotEmpty() ? '<span class="org-count">'.$kids->count().' reports</span>' : '')
                .'</'.explode(' ', $tag)[0].'>';
            if ($kids->isNotEmpty()) {
                $html .= '<ul>'.$renderPeople($kids).'</ul>';
            }
            $html .= '</li>';
        }

        return $html;
    };
    // By-reference so the closure works in both modes (these are only set in "designations").
    $render = function ($nodes) use (&$render, &$children, &$employees) {
        $html = '';
        foreach ($nodes as $d) {
            $people = $employees->get($d->id, collect());
            $html .= '<li><div class="org-node"><div class="org-node__head"><strong>'.e($d->name).'</strong><small>'.e($d->department->name ?? '').'</small></div><div class="org-node__people">';
            foreach ($people as $p) {
                $tag = auth()->user()->can('hrm.view_employees') ? 'a href="'.route('hrm.employees.show', $p).'"' : 'div';
                $html .= '<'.$tag.' class="org-person"><span class="app-avatar">'.e(\App\Support\Hrm\Hrm::initials($p->name)).'</span>'.e($p->name).'</'.explode(' ', $tag)[0].'>';
            }
            if ($people->isEmpty()) {
                $html .= '<div class="org-vacant">Vacant</div>';
            }
            $html .= '</div></div>';
            $kids = $children->get($d->id, collect());
            if ($kids->isNotEmpty()) {
                $html .= '<ul>'.$render($kids).'</ul>';
            }
            $html .= '</li>';
        }

        return $html;
    };
@endphp

@section('content')
<div class="app-content">
    <x-admin.page-header title="Organization Chart" :subtitle="$mode === 'people' ? 'Who reports to whom — set “Reports to” on each employee.' : 'Designation structure — set “Reports to” on each designation.'">
        <div class="btn-group">
            <a href="{{ route('hrm.org-chart') }}" class="btn {{ $mode === 'people' ? 'btn-primary' : 'btn-light' }}"><i class="bi bi-people"></i> People</a>
            <a href="{{ route('hrm.org-chart', ['view' => 'designations']) }}" class="btn {{ $mode === 'designations' ? 'btn-primary' : 'btn-light' }}"><i class="bi bi-diagram-3"></i> Designations</a>
        </div>
    </x-admin.page-header>

    <x-admin.card>
        @if ($mode === 'people')
            @if ($peopleRoots->isEmpty())
                <x-admin.empty icon="people" text="No active employees yet." />
            @else
                <div class="org-wrap"><ul class="org">{!! $renderPeople($peopleRoots) !!}</ul></div>
            @endif
        @else
            @if ($roots->isEmpty())
                <x-admin.empty icon="diagram-3" text="No designations yet. Create designations and set who they report to." />
            @else
                <div class="org-wrap"><ul class="org">{!! $render($roots) !!}</ul></div>
            @endif
        @endif
    </x-admin.card>
</div>
@endsection
