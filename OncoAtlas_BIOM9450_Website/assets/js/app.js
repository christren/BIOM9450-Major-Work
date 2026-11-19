'use strict';
/* Native JavaScript/AJAX. No Bootstrap or chart library required. */
(() => {
  const $ = (id) => document.getElementById(id);
  const safe = (v) => String(v ?? '').replace(/[&<>"']/g, (ch) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  const maybe = (v) => v === null || v === '' || v === undefined ? '—' : safe(v);
  const number = (v) => new Intl.NumberFormat('en-AU').format(Number(v || 0));
  const debounce = (fn, ms = 330) => { let timer; return (...a) => { clearTimeout(timer); timer = setTimeout(() => fn(...a), ms); }; };
  async function fetchJSON(url, opts = {}) {
    const response = await fetch(url, {credentials:'same-origin',...opts});
    if (response.status === 401) {window.location.href = 'login.php'; throw new Error('Session expired');}
    const data = await response.json().catch(() => {throw new Error('Unexpected server response');});
    if (!response.ok) throw new Error(data.error || 'Unable to load data');
    return data;
  }
  function tableState({form, body, status, prev, next, pageLabel, endpoint, row, colspan = 8, extraParams}) {
    if (!body) return;
    let currentPage = 1, pageCount = 1, requestID = 0;
    async function update() {
      const id = ++requestID;
      status.textContent = 'Loading results…';body.setAttribute('aria-busy','true');
      try {
        const qs = new URLSearchParams(new FormData(form));
        if (extraParams) Object.entries(extraParams()).forEach(([k,v])=>qs.set(k,v));
        qs.set('page', String(currentPage));
        const data = await fetchJSON(endpoint + '?' + qs.toString());
        if (id !== requestID) return;
        currentPage = data.page;pageCount = data.pages;
        body.innerHTML = data.rows.length ? data.rows.map(row).join('') : `<tr><td class="no-results" colspan="${colspan}">No matching records. Try changing your filters.</td></tr>`;
        status.textContent = `${number(data.total)} matching record${Number(data.total) === 1 ? '' : 's'}`;
        pageLabel.textContent = `Page ${currentPage} of ${pageCount}`;
        prev.disabled = currentPage <= 1;next.disabled = currentPage >= pageCount;
      } catch (err) {
        if (id !== requestID) return;
        status.textContent = err.message; body.innerHTML = `<tr><td colspan="${colspan}" class="no-results">Unable to load this table. Check your connection and try again.</td></tr>`;
      } finally {if (id === requestID) body.removeAttribute('aria-busy');}
    }
    const refresh = () => {currentPage=1;update();};
    form.addEventListener('input',debounce(refresh));form.addEventListener('change',refresh);
    prev.addEventListener('click',()=>{if(currentPage>1){currentPage--;update();}});
    next.addEventListener('click',()=>{if(currentPage<pageCount){currentPage++;update();}});
    update();return {refresh};
  }
  const OncoTables = {
    patients() {
      const form=$('patientsFilters'); if(!form)return;
      const controls=tableState({form,body:$('patientsBody'),status:$('patientsStatus'),prev:$('patientsPrev'),next:$('patientsNext'),pageLabel:$('patientsPage'),endpoint:'api/patients.php',colspan:8,
        row:(p)=>`<tr><td><strong>${safe(p.Name)}</strong>${Number(p.DemographicsSynthetic)?'<small class="table-subline">Teaching profile</small>':''}</td><td class="mono">${maybe(p.TCGAPatientID)}</td><td><span class="code-pill">${maybe(p.StudyCode?.toUpperCase())}</span></td><td>${maybe(p.Age)}</td><td>${maybe(p.Sex)}</td><td>${number(p.Samples)}</td><td>${number(p.Mutations)}</td><td><a class="row-link" href="patient.php?id=${Number(p.PatientID)}">Open <span aria-hidden="true">›</span></a></td></tr>`});
      $('resetPatients')?.addEventListener('click',()=>{form.reset();controls.refresh();});
    },
    mutations() {
      const form=$('mutationFilters');if(!form)return;
      const controls=tableState({form,body:$('mutationsBody'),status:$('mutationStatus'),prev:$('mutationsPrev'),next:$('mutationsNext'),pageLabel:$('mutationsPage'),endpoint:'api/mutations.php',colspan:9,
        row:(m)=>`<tr><td><a class="gene-link" href="genes.php?gene=${encodeURIComponent(m.GeneSymbol || '')}">${maybe(m.GeneSymbol)}</a></td><td>${maybe(m.Chromosome)}</td><td class="mono">${maybe(m.MutationPosition)}</td><td class="mono variant-alleles">${maybe(m.ReferenceAllele)} <span>→</span> ${maybe(m.AlternateAllele)}</td><td>${maybe(m.MutationType)}</td><td><span class="impact ${safe((m.Impact || '').toLowerCase())}">${maybe(m.Impact)}</span></td><td class="mono">${maybe(m.ProteinChange)}</td><td><a class="mono sample-link" href="patient.php?id=${Number(m.PatientID)}">${maybe(m.TCGASampleBarcode)}</a></td><td><span class="code-pill">${maybe(m.StudyCode?.toUpperCase())}</span></td></tr>`});
      $('clearMutations')?.addEventListener('click',()=>{form.reset();form.elements.patient.value='';controls.refresh();});
    },
    genes() {
      const form=document.createElement('form');
      const search=$('geneSearch'),cohort=$('geneCohort');if(!search)return;
      const controls=tableState({form,body:$('geneBody'),status:$('geneStatus'),prev:$('genesPrev'),next:$('genesNext'),pageLabel:$('genesPage'),endpoint:'api/genes.php',colspan:5,extraParams:()=>({q:search.value,cancer:cohort.value}),
        row:(g)=>`<tr><td><strong class="gene-symbol">${safe(g.GeneSymbol)}</strong></td><td>${number(g.MutationCount)}</td><td>${number(g.SampleCount)}</td><td>${number(g.PatientCount)}</td><td><a class="row-link" href="mutations.php?gene=${encodeURIComponent(g.GeneSymbol)}">View mutations <span>›</span></a></td></tr>`});
      const uri=new URLSearchParams(window.location.search).get('gene');if(uri){search.value=uri;controls.refresh();}
      search.addEventListener('input',debounce(controls.refresh));cohort.addEventListener('change',controls.refresh);
      $('geneReset')?.addEventListener('click',()=>{search.value='';cohort.value='';controls.refresh();});
    }
  };
  const chartColors=['#6258e8','#009b96','#ef9a47','#4884dd','#b56bd8','#eb677d','#6f83a6','#5ca98f'];
  function makeBars(container, rows, nameKey, valueKey, empty='No chart data available') {
    if(!container)return;container.replaceChildren();
    if(!rows.length){container.textContent=empty;return;}
    const maximum=Math.max(1,...rows.map(r=>Number(r[valueKey] || 0)));
    const inner=document.createElement('div');inner.className='chart-bars';
    rows.forEach((r,i)=>{
      const value=Number(r[valueKey]||0),ratio=Math.max(0,Math.min(100,value/maximum*100));
      const item=document.createElement('div');item.className='chart-row';
      const label=document.createElement('span');label.className='chart-label';label.textContent=String(r[nameKey]);label.title=String(r[nameKey]);
      const track=document.createElement('div');track.className='chart-track';
      const bar=document.createElement('div');bar.className='chart-bar';bar.style.width=ratio+'%';bar.style.background=chartColors[i%chartColors.length];
      const figure=document.createElement('strong');figure.className='chart-number';figure.textContent=number(value);
      track.appendChild(bar);item.append(label,track,figure);inner.appendChild(item);
    });
    container.appendChild(inner);
  }
  const OncoCharts={
    async loadOverview(){
      try {const data=await fetchJSON('api/stats.php?scope=overview');
        makeBars($('cohortChart'),data.cohorts.map(c=>({label:String(c.StudyCode).toUpperCase(),n:c.Patients})),'label','n');
        makeBars($('geneChart'),data.topGenes.map(g=>({label:g.GeneSymbol,n:g.Mutations})),'label','n');
      }catch(e){if($('cohortChart'))$('cohortChart').textContent=e.message;if($('geneChart'))$('geneChart').textContent=e.message;}
    },
    async loadAnalytics(){
      try{const data=await fetchJSON('api/stats.php?scope=analytics');
        makeBars($('mutationCohortChart'),data.cohorts.map(c=>({label:String(c.StudyCode).toUpperCase(),n:c.Mutations})),'label','n');
        makeBars($('classChart'),data.classes.map(c=>({label:c.MutationType,n:c.Mutations})),'label','n');
      }catch(e){if($('mutationCohortChart'))$('mutationCohortChart').textContent=e.message;}
      const form=$('compareForm');if(!form)return;
      const compare=async(e)=>{
        if(e)e.preventDefault();const gene=$('compareGene').value.trim();const c=$('geneCompareChart');
        if(!gene){c.textContent='Enter a gene symbol.';return;}c.textContent='Comparing cohorts…';
        try{const data=await fetchJSON('api/stats.php?scope=compare&gene='+encodeURIComponent(gene));
          makeBars(c,data.rows.map(r=>({label:String(r.StudyCode).toUpperCase(),n:r.Mutations})),'label','n','No observations were found for this gene.');
        }catch(err){c.textContent=err.message;}
      };form.addEventListener('submit',compare);compare();
    }
  };
  const OncoUpload={
    init(){const field=$('uploadPatient'),barcode=$('sampleBarcode'),target=$('sampleLookup');if(!field)return;
      const load=debounce(async()=>{
        const id=field.value.trim();target.replaceChildren();if(!/^\d+$/.test(id)){target.textContent='Enter a patient ID to see existing samples.';return;}
        target.textContent='Looking up samples…';
        try {const data=await fetchJSON('api/patient_samples.php?patient_id='+encodeURIComponent(id));target.replaceChildren();
          const p=document.createElement('p');p.textContent=data.patient.Name+' · '+(data.patient.TCGAPatientID||'Manual profile');target.appendChild(p);
          if(!data.samples.length){const s=document.createElement('small');s.textContent='No existing samples. Enter a new barcode.';target.appendChild(s);return;}
          const hint=document.createElement('small');hint.textContent='Click an existing sample to use its barcode:';target.appendChild(hint);
          data.samples.forEach(s=>{const b=document.createElement('button');b.type='button';b.className='sample-choice';b.textContent=s.TCGASampleBarcode||('Sample #'+s.SampleID);b.addEventListener('click',()=>{barcode.value=s.TCGASampleBarcode||'';barcode.focus();});target.appendChild(b);});
        } catch(e){target.textContent=e.message;}
      },300);
      field.addEventListener('input',load);if(field.value)load();
    }
  };
  document.addEventListener('DOMContentLoaded',()=>{
    const toggle=$('menuToggle'),side=$('sidebar');
    toggle?.addEventListener('click',()=>side?.classList.toggle('open'));
    document.addEventListener('keydown',e=>{if(e.key==='Escape')side?.classList.remove('open');});
    document.querySelectorAll('.nav-link').forEach(link=>link.addEventListener('click',()=>side?.classList.remove('open')));
  });
  window.OncoTables=OncoTables;window.OncoCharts=OncoCharts;window.OncoUpload=OncoUpload;
})();
