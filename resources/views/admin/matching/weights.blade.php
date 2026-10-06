@extends('layouts.app')
@section('title', 'Matching Weights — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Scholarship Matching Weights</h2>
</div>

@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="card">
    <p class="muted">Configure the relative importance of each matching factor. Weights must total exactly 100%.</p>
    <form method="POST" action="{{ route('admin.scholarship-matching.weights.update') }}">
        @csrf @method('PUT')
        <div class="form-row">
            <div class="form-group">
                <label>Academic Qualification (%)</label>
                <input type="number" name="academic_weight" step="0.01" min="0" max="100" value="{{ old('academic_weight', $weights->academic_weight ?? 30) }}" required oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label>Financial Eligibility (%)</label>
                <input type="number" name="financial_weight" step="0.01" min="0" max="100" value="{{ old('financial_weight', $weights->financial_weight ?? 25) }}" required oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label>Program Match (%)</label>
                <input type="number" name="program_weight" step="0.01" min="0" max="100" value="{{ old('program_weight', $weights->program_weight ?? 20) }}" required oninput="calcTotal()">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Year-Level Match (%)</label>
                <input type="number" name="year_level_weight" step="0.01" min="0" max="100" value="{{ old('year_level_weight', $weights->year_level_weight ?? 15) }}" required oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label>Document Readiness (%)</label>
                <input type="number" name="document_readiness_weight" step="0.01" min="0" max="100" value="{{ old('document_readiness_weight', $weights->document_readiness_weight ?? 10) }}" required oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label>Current Total</label>
                <div id="totalDisplay" class="weight-total">100.00%</div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save Weights</button>
    </form>
</div>

<script>
function calcTotal(){
    const f=['academic_weight','financial_weight','program_weight','year_level_weight','document_readiness_weight'];
    let t=0;f.forEach(n=>{t+=parseFloat(document.querySelector(`[name="${n}"]`).value||0)});
    const d=document.getElementById('totalDisplay');
    d.textContent=t.toFixed(2)+'%';
    d.style.color=Math.abs(t-100)<0.02?'#16a34a':'#dc2626';
}
document.addEventListener('DOMContentLoaded',calcTotal);
</script>
@endsection