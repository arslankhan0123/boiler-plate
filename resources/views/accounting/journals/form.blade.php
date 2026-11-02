@extends('layouts.admin.main')
@section('title', $journal->exists ? 'Edit journal' : 'New journal')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$journal->exists ? 'Edit journal '.$journal->reference : 'New journal entry'" :back="route('accounting.journals.index')" />
    <form method="POST" action="{{ $journal->exists ? route('accounting.journals.update', $journal) : route('accounting.journals.store') }}" id="jform">
        @csrf @if ($journal->exists)@method('PUT')@endif
        @error('lines')<div class="alert alert-danger">{{ $message }}</div>@enderror
        <div class="row g-3 mb-3">
            <div class="col-md-3"><x-admin.input name="reference" label="Reference" :value="$journal->reference" /></div>
            <div class="col-md-3"><x-admin.input name="date" label="Date" type="date" :value="old('date', optional($journal->date)->format('Y-m-d'))" required /></div>
            <div class="col-md-6"><x-admin.input name="memo" label="Memo" :value="$journal->memo" optional /></div>
        </div>
        <x-admin.card flush>
            <div class="table-responsive"><table class="table app-table align-middle mb-0" id="lines">
                <thead><tr><th style="width:40%">Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th>Memo</th><th></th></tr></thead>
                <tbody>
                @php $rows = old('lines', collect($lines)->map(fn($l)=>['account_id'=>$l->account_id,'debit'=>$l->debit,'credit'=>$l->credit,'memo'=>$l->memo])->all()); @endphp
                @php $rows = $rows ?: [['account_id'=>'','debit'=>'','credit'=>'','memo'=>''], ['account_id'=>'','debit'=>'','credit'=>'','memo'=>'']]; @endphp
                @foreach ($rows as $i => $r)
                    <tr>
                        <td><select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm"><option value="">— Account —</option>@foreach ($accounts as $id => $n)<option value="{{ $id }}" @selected((string)($r['account_id']??'')===(string)$id)>{{ $n }}</option>@endforeach</select></td>
                        <td><input name="lines[{{ $i }}][debit]" type="number" step="any" min="0" value="{{ $r['debit'] ?: '' }}" class="form-control form-control-sm text-end jd" placeholder="0.00"></td>
                        <td><input name="lines[{{ $i }}][credit]" type="number" step="any" min="0" value="{{ $r['credit'] ?: '' }}" class="form-control form-control-sm text-end jc" placeholder="0.00"></td>
                        <td><input name="lines[{{ $i }}][memo]" value="{{ $r['memo'] ?? '' }}" class="form-control form-control-sm"></td>
                        <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger rm">&times;</button></td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr class="fw-bold"><td class="text-end">Totals</td><td class="text-end"><span id="td">0.00</span></td><td class="text-end"><span id="tc">0.00</span></td><td colspan="2"><span id="diff" class="small"></span></td></tr></tfoot>
            </table></div>
        </x-admin.card>
        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn btn-light" id="addrow"><i class="bi bi-plus-lg"></i> Add line</button>
            <div class="ms-auto d-flex gap-2">
                <button type="submit" name="action" value="draft" class="btn btn-outline-primary"><i class="bi bi-save me-1"></i>Save draft</button>
                <button type="submit" name="action" value="post" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save &amp; Post</button>
            </div>
        </div>
    </form>
</div>
<script>
(function(){
    const tb = document.querySelector('#lines tbody');
    const accOpts = tb.querySelector('select')?.innerHTML || '';
    function recalc(){
        let d=0,c=0;
        tb.querySelectorAll('tr').forEach(r=>{ d+=parseFloat(r.querySelector('.jd').value||0); c+=parseFloat(r.querySelector('.jc').value||0); });
        document.getElementById('td').textContent=d.toFixed(2);
        document.getElementById('tc').textContent=c.toFixed(2);
        const diff=document.getElementById('diff');
        if(Math.abs(d-c)<0.005 && d>0){ diff.textContent='Balanced'; diff.className='small text-success'; }
        else { diff.textContent='Out by '+(Math.abs(d-c)).toFixed(2); diff.className='small text-danger'; }
    }
    function reindex(){ tb.querySelectorAll('tr').forEach((r,i)=>r.querySelectorAll('[name]').forEach(el=>el.name=el.name.replace(/lines\[\d+\]/,'lines['+i+']'))); }
    document.getElementById('addrow').addEventListener('click',()=>{
        const i=tb.children.length;
        const tr=document.createElement('tr');
        tr.innerHTML='<td><select name="lines['+i+'][account_id]" class="form-select form-select-sm">'+accOpts+'</select></td>'+
          '<td><input name="lines['+i+'][debit]" type="number" step="any" min="0" class="form-control form-control-sm text-end jd" placeholder="0.00"></td>'+
          '<td><input name="lines['+i+'][credit]" type="number" step="any" min="0" class="form-control form-control-sm text-end jc" placeholder="0.00"></td>'+
          '<td><input name="lines['+i+'][memo]" class="form-control form-control-sm"></td>'+
          '<td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger rm">&times;</button></td>';
        tb.appendChild(tr); recalc();
    });
    tb.addEventListener('input',e=>{ if(e.target.classList.contains('jd')||e.target.classList.contains('jc')) recalc(); });
    tb.addEventListener('click',e=>{ if(e.target.classList.contains('rm')){ if(tb.children.length>2){ e.target.closest('tr').remove(); reindex(); recalc(); } } });
    recalc();
})();
</script>
@endsection
