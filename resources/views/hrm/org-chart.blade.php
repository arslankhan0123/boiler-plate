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
    .org-vacant { font-size: .75rem; color: var(--app-text-muted); font-style: italic; padding: .25rem; }
</style>
@endpush

@php
    $render = function ($nodes) use (&$render, $children, $employees) {
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
    <x-admin.page-header title="Organization Chart" subtitle="Who reports to whom. Change it from Designations → “Reports to”.">
        @can('hrm.manage_org')
            <x-admin.button variant="light" :href="route('hrm.designations.index')" icon="pencil">Edit Structure</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.card>
        @if ($roots->isEmpty())
            <x-admin.empty icon="diagram-3" text="No designations yet. Create designations and set who they report to." />
        @else
            <div class="org-wrap"><ul class="org">{!! $render($roots) !!}</ul></div>
        @endif
    </x-admin.card>
</div>
@endsection
