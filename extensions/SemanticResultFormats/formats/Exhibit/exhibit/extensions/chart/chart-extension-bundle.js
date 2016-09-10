

/* bar-chart-view.js */
Exhibit.BarChartView=function(C,B){this._div=C;
this._uiContext=B;
this._settings={};
this._accessors={getPointLabel:function(F,E,D){D(E.getObject(F,"label"));
},getProxy:function(F,E,D){D(F);
},getColorKey:null};
this._axisFuncs={x:function(D){return D;
}};
this._axisInverseFuncs={x:function(D){return D;
}};
this._colorKeyCache=new Object();
this._maxColor=0;
var A=this;
this._listener={onItemsChanged:function(){A._reconstruct();
}};
B.getCollection().addListener(this._listener);
};
Exhibit.BarChartView._settingSpecs={"plotHeight":{type:"int",defaultValue:400},"bubbleWidth":{type:"int",defaultValue:400},"bubbleHeight":{type:"int",defaultValue:300},"xAxisMin":{type:"float",defaultValue:Number.POSITIVE_INFINITY},"xAxisMax":{type:"float",defaultValue:Number.NEGATIVE_INFINITY},"xAxisType":{type:"enum",defaultValue:"linear",choices:["linear","log"]},"yAxisMin":{type:"float",defaultValue:Number.POSITIVE_INFINITY},"yAxisMax":{type:"float",defaultValue:Number.NEGATIVE_INFINITY},"yAxisType":{type:"enum",defaultValue:"linear",choices:["linear","log"]},"xLabel":{type:"text",defaultValue:"x"},"yLabel":{type:"text",defaultValue:"y"},"color":{type:"text",defaultValue:"#5D7CBA"},"colorCoder":{type:"text",defaultValue:null},"scroll":{type:"boolean",defaultValue:false}};
Exhibit.BarChartView._accessorSpecs=[{accessorName:"getProxy",attributeName:"proxy"},{accessorName:"getPointLabel",attributeName:"pointLabel"},{accessorName:"getXY",alternatives:[{bindings:[{attributeName:"xy",types:["float","text"],bindingNames:["x","y"]}]},{bindings:[{attributeName:"x",type:"float",bindingName:"x"},{attributeName:"y",type:"text",bindingName:"y"}]}]},{accessorName:"getColorKey",attributeName:"colorKey",type:"text"}];
Exhibit.BarChartView.create=function(D,C,B){var A=new Exhibit.BarChartView(C,Exhibit.UIContext.create(D,B));
Exhibit.BarChartView._configure(A,D);
A._internalValidate();
A._initializeUI();
return A;
};
Exhibit.BarChartView.createFromDOM=function(D,C,B){var E=Exhibit.getConfigurationFromDOM(D);
var A=new Exhibit.BarChartView(C!=null?C:D,Exhibit.UIContext.createFromDOM(D,B));
Exhibit.SettingsUtilities.createAccessorsFromDOM(D,Exhibit.BarChartView._accessorSpecs,A._accessors);
Exhibit.SettingsUtilities.collectSettingsFromDOM(D,Exhibit.BarChartView._settingSpecs,A._settings);
Exhibit.BarChartView._configure(A,E);
A._internalValidate();
A._initializeUI();
return A;
};
Exhibit.BarChartView._configure=function(A,C){Exhibit.SettingsUtilities.createAccessors(C,Exhibit.BarChartView._accessorSpecs,A._accessors);
Exhibit.SettingsUtilities.collectSettings(C,Exhibit.BarChartView._settingSpecs,A._settings);
A._axisFuncs.x=Exhibit.BarChartView._getAxisFunc(A._settings.xAxisType);
A._axisInverseFuncs.x=Exhibit.BarChartView._getAxisInverseFunc(A._settings.xAxisType);
var B=A._accessors;
A._getXY=function(F,E,D){B.getProxy(F,E,function(G){B.getXY(G,E,D);
});
};
};
Exhibit.BarChartView._getAxisFunc=function(A){if(A=="log"){return function(B){return(Math.log(B)/Math.log(10));
};
}else{return function(B){return B;
};
}};
Exhibit.BarChartView._getAxisInverseFunc=function(A){if(A=="log"){return function(B){return Math.pow(10,B);
};
}else{return function(B){return B;
};
}};
Exhibit.BarChartView._colors=["FF9000","5D7CBA","A97838","8B9BBA","FFC77F","003EBA","29447B","543C1C"];
Exhibit.BarChartView._mixColor="FFFFFF";
Exhibit.BarChartView.evaluateSingle=function(C,B,A){return C.evaluateSingleOnItem(B,A).value;
};
Exhibit.BarChartView.prototype.dispose=function(){this._uiContext.getCollection().removeListener(this._listener);
this._toolboxWidget.dispose();
this._toolboxWidget=null;
this._dom.dispose();
this._dom=null;
this._uiContext.dispose();
this._uiContext=null;
this._div.innerHTML="";
this._div=null;
};
Exhibit.BarChartView.prototype._internalValidate=function(){if("getColorKey" in this._accessors){if("colorCoder" in this._settings){this._colorCoder=this._uiContext.getExhibit().getComponent(this._settings.colorCoder);
}if(this._colorCoder==null){this._colorCoder=new Exhibit.DefaultColorCoder(this._uiContext);
}}};
Exhibit.BarChartView.prototype._initializeUI=function(){var A=this;
var B="_gradientPoints" in this._colorCoder?"gradient":{};
this._div.innerHTML="";
this._dom=Exhibit.ViewUtilities.constructPlottingViewDom(this._div,this._uiContext,true,{onResize:function(){A._reconstruct();
}},B);
this._toolboxWidget=Exhibit.ToolboxWidget.createFromDOM(this._div,this._div,this._uiContext);
this._dom.plotContainer.className="exhibit-barChartView-plotContainer";
this._dom.plotContainer.style.height=this._settings.plotHeight+"px";
this._reconstruct();
};
Exhibit.BarChartView.prototype._reconstruct=function(){var E=this;
var c=this._uiContext.getCollection();
var j=this._uiContext.getDatabase();
var t=this._settings;
var u=this._accessors;
this._dom.plotContainer.innerHTML="";
var b=E._axisFuncs.x;
var B=E._axisInverseFuncs.x;
var K=c.countRestrictedItems();
var F=[];
this._dom.legendWidget.clear();
if(K>0){var P=c.getRestrictedItems();
var J=(this._accessors.getColorKey!=null);
var Q={};
var W=t.xAxisMin;
var C=t.xAxisMax;
P.visit(function(AC){var AD=[];
E._getXY(AC,j,function(AE){if("x" in AE&&"y" in AE){AD.push(AE);
}});
if(AD.length>0){var k=null;
if(J){k=new Exhibit.Set();
u.getColorKey(AC,j,function(AE){k.add(AE);
});
}for(var x=0;
x<AD.length;
x++){var AB=AD[x];
var y=AB.x+","+AB.y;
if(y in Q){var z=Q[y];
z.items.push(AC);
if(J){z.colorKeys.addSet(k);
}}else{try{AB.scaledX=b(AB.x);
if(!isFinite(AB.scaledX)){continue;
}}catch(AA){continue;
}var z={xy:AB,items:[AC]};
if(J){z.colorKeys=k;
}Q[y]=z;
W=Math.min(W,AB.scaledX);
C=Math.max(C,AB.scaledX);
}}}else{F.push(AC);
}});
var T=C-W;
var d=1;
if(T>1){while(d*20<T){d*=10;
}}else{while(d<T*20){d/=10;
}}W=Math.floor(W/d)*d;
C=Math.ceil(C/d)*d;
t.xAxisMin=W;
t.xAxisMax=C;
var n=document.createElement("div");
n.className=SimileAjax.Platform.browser.isIE?"exhibit-barChartView-canvasFrame-ie":"exhibit-barChartView-canvasFrame";
this._dom.plotContainer.appendChild(n);
if(E._settings.scroll){n.style.overflow="scroll";
}var Y=document.createElement("table");
var e=document.createElement("tbody");
var q=document.createElement("tr");
var w=document.createElement("td");
var M=document.createElement("td");
var i=document.createElement("div");
var O=document.createElement("div");
Y.style.width="100%";
O.style.position="relative";
O.style.width="100%";
w.appendChild(i);
M.appendChild(O);
q.appendChild(w);
q.appendChild(M);
e.appendChild(q);
Y.appendChild(e);
n.appendChild(Y);
var H=document.createElement("div");
H.className="exhibit-barChartView-canvas";
H.style.height="100%";
O.appendChild(H);
var Z=document.createElement("div");
Z.className=SimileAjax.Platform.browser.isIE?"exhibit-barChartView-yAxis-ie":"exhibit-barChartView-yAxis";
this._dom.plotContainer.appendChild(Z);
var l=document.createElement("div");
l.style.position="relative";
l.style.height="100%";
Z.appendChild(l);
var v=document.createElement("div");
v.className="exhibit-barChartView-yAxisName";
v.innerHTML=t.yLabel;
l.appendChild(v);
var f={mixed:false,missing:false,others:false,keys:new Exhibit.Set()};
var N=function(k){var AB=k.items;
var y=t.color;
if(J){y=E._colorCoder.translateSet(k.colorKeys,f);
}var AC=k.xy;
var z=document.createElement("div");
var AD=document.createTextNode(AC.y);
z.appendChild(AD);
z.style.height="1.5em";
i.appendChild(z);
var AE=document.createElement("div");
AE.style.position="relative";
AE.style.height="1.5em";
AE.style.zIndex="2";
var AA=document.createElement("div");
AA.className="exhibit-barChartView-bar";
AA.style.backgroundColor=y;
AA.style.textAlign="right";
AA.style.left="0";
var x=Math.floor(100*(b(AC.x)-W)/(C-W));
AA.style.width=x+"%";
AA.style.borderStyle="solid";
AA.style.borderWidth="1px";
AA.style.paddingLeft="0px";
var AD=document.createTextNode(AC.x);
AA.appendChild(AD);
AE.appendChild(AA);
H.appendChild(AE);
SimileAjax.WindowManager.registerEvent(AA,"click",function(AG,AF,AH){E._openPopup(AA,AB);
});
SimileAjax.WindowManager.registerEvent(z,"click",function(AG,AF,AH){E._openPopup(z,AB);
});
};
for(xyKey in Q){N(Q[xyKey]);
}w.style.width="1px";
Y.style.tableLayout="auto";
var U=document.createElement("div");
U.className="exhibit-barChartView-xAxis";
var A=document.createElement("div");
A.style.position="relative";
A.style.left=0;
U.appendChild(A);
var m=H.offsetWidth;
var G=H.offsetHeight;
var I=m/(C-W);
H.style.display="none";
var g=function(x,k){if(x>=1000000){return function(y){return Math.floor(k(y)/1000000)+"M";
};
}else{if(x>=1000){return function(y){return Math.floor(k(y)/1000)+"K";
};
}else{return function(y){return k(y);
};
}}};
var X=g(d,B);
for(var h=W+d;
h<C;
h+=d){var D=Math.floor((h-W)*I);
var s=document.createElement("div");
s.className="exhibit-barChartView-gridLine";
s.style.width="1px";
s.style.left=D+"px";
s.style.top="0px";
s.style.height="100%";
s.style.zIndex="1";
H.appendChild(s);
var V=document.createElement("div");
V.className="exhibit-barChartView-xAxisLabel";
V.style.left=D+"px";
V.innerHTML=X(h);
A.appendChild(V);
}var p=document.createElement("div");
p.className="exhibit-barChartView-xAxisName";
p.innerHTML=t.xLabel;
A.appendChild(p);
O.appendChild(U);
H.style.display="block";
if(J){var a=this._dom.legendWidget;
var r=this._colorCoder;
var R=f.keys.toArray().sort();
if(this._colorCoder._gradientPoints!=null){a.addGradient(this._colorCoder._gradientPoints);
}else{for(var o=0;
o<R.length;
o++){var L=R[o];
var S=r.translate(L);
a.addEntry(S,L);
}}if(f.others){a.addEntry(r.getOthersColor(),r.getOthersLabel());
}if(f.mixed){a.addEntry(r.getMixedColor(),r.getMixedLabel());
}if(f.missing){a.addEntry(r.getMissingColor(),r.getMissingLabel());
}}}this._dom.setUnplottableMessage(K,F);
};
Exhibit.BarChartView.prototype._openPopup=function(B,A){Exhibit.ViewUtilities.openBubbleForItems(B,A,this._uiContext);
};


/* pivot-table-view.js */
Exhibit.PivotTableView=function(C,B){this._div=C;
this._uiContext=B;
this._rowPath=null;
this._columnPath=null;
this._cellExpression=null;
this._settings={};
var A=this;
this._listener={onItemsChanged:function(){A._reconstruct();
}};
B.getCollection().addListener(this._listener);
};
Exhibit.PivotTableView.create=function(D,C,B){var A=new Exhibit.PivotTableView(C,Exhibit.UIContext.create(D,B));
Exhibit.PivotTableView._configure(A,D);
A._initializeUI();
return A;
};
Exhibit.PivotTableView.createFromDOM=function(D,C,B){var E=Exhibit.getConfigurationFromDOM(D);
var A=new Exhibit.PivotTableView(C!=null?C:D,Exhibit.UIContext.createFromDOM(D,B));
A._columnPath=Exhibit.PivotTableView._parsePath(Exhibit.getAttribute(D,"column"));
A._rowPath=Exhibit.PivotTableView._parsePath(Exhibit.getAttribute(D,"row"));
A._cellExpression=Exhibit.PivotTableView._parseExpression(Exhibit.getAttribute(D,"cell"));
Exhibit.PivotTableView._configure(A,E);
A._initializeUI();
return A;
};
Exhibit.PivotTableView._configure=function(A,B){if("column" in B){A._columnPath=Exhibit.PivotTableView._parsePath(B.column);
}if("row" in B){A._rowPath=Exhibit.PivotTableView._parsePath(B.row);
}if("cell" in B){A._cellExpression=Exhibit.PivotTableView._parseExpression(B.cell);
}};
Exhibit.PivotTableView._parseExpression=function(A){try{return Exhibit.ExpressionParser.parse(A);
}catch(B){SimileAjax.Debug.exception(B,"Error parsing expression "+A);
}return null;
};
Exhibit.PivotTableView._parsePath=function(A){try{var C=Exhibit.ExpressionParser.parse(A);
if(C.isPath()){return C.getPath();
}else{SimileAjax.Debug.log("Expecting a path but got a full expression: "+A);
}}catch(B){SimileAjax.Debug.exception(B,"Error parsing expression "+A);
}return null;
};
Exhibit.PivotTableView.prototype.dispose=function(){this._uiContext.getCollection().removeListener(this._listener);
this._toolboxWidget.dispose();
this._toolboxWidget=null;
this._collectionSummaryWidget.dispose();
this._collectionSummaryWidget=null;
this._uiContext.dispose();
this._uiContext=null;
this._div.innerHTML="";
this._dom=null;
this._div=null;
};
Exhibit.PivotTableView.prototype._initializeUI=function(){var A=this;
this._div.innerHTML="";
this._dom=Exhibit.PivotTableView.constructDom(this._div);
this._collectionSummaryWidget=Exhibit.CollectionSummaryWidget.create({},this._dom.collectionSummaryDiv,this._uiContext);
this._toolboxWidget=Exhibit.ToolboxWidget.createFromDOM(this._div,this._div,this._uiContext);
this._reconstruct();
};
Exhibit.PivotTableView.prototype._reconstruct=function(){this._dom.tableContainer.innerHTML="";
var A=this._uiContext.getCollection().countRestrictedItems();
if(A>0){var B=this._uiContext.getCollection().getRestrictedItems();
if(this._columnPath!=null&&this._rowPath!=null&&this._cellExpression!=null){this._makeTable(B);
}}};
Exhibit.PivotTableView.prototype._makeTable=function(Q){var P=this;
var N=this._uiContext.getDatabase();
var D=this._rowPath.walkForward(Q,"item",N).getSet();
var J=this._columnPath.walkForward(Q,"item",N).getSet();
var R=Exhibit.PivotTableView._sortValues(D);
var E=Exhibit.PivotTableView._sortValues(J);
var M=R.length;
var Y=E.length;
var Z="#eee";
var F="#fff";
var V=document.createElement("table");
V.cellPadding=2;
V.cellSpacing=0;
var T=0;
var B,K;
for(var X=0;
X<Y;
X++){var L=0;
B=V.insertRow(T++);
K=B.insertCell(L++);
if(X>0){K=B.insertCell(L++);
K.rowSpan=Y-X+1;
K.style.backgroundColor=(X%2)==0?F:Z;
K.innerHTML="\u00a0";
}K=B.insertCell(L++);
K.colSpan=Y-X+1;
K.style.backgroundColor=(X%2)==1?F:Z;
K.innerHTML=E[X].label;
}B=V.insertRow(T++);
K=B.insertCell(0);
K=B.insertCell(1);
K.style.backgroundColor=(Y%2)==0?F:Z;
K.innerHTML="\u00a0";
K=B.insertCell(2);
for(var O=0;
O<M;
O++){var L=0;
var C=R[O];
var U=C.value;
B=V.insertRow(T++);
K=B.insertCell(L++);
K.innerHTML=R[O].label;
K.style.borderBottom="1px solid #aaa";
var S=this._rowPath.evaluateBackward(U,D.valueType,Q,N).getSet();
for(var X=0;
X<Y;
X++){var W=E[X];
var H=W.value;
K=B.insertCell(L++);
K.style.backgroundColor=(X%2)==1?F:Z;
K.style.borderBottom="1px solid #ccc";
K.title=C.label+" / "+W.label;
var G=this._columnPath.evaluateBackward(H,J.valueType,S,N);
var A=this._cellExpression.evaluate({"value":G.getSet()},{"value":G.valueType},"value",N);
if(A.valueType=="number"&&A.values.size()==1){A.values.visit(function(a){if(a!=0){K.appendChild(document.createTextNode(a));
}else{K.appendChild(document.createTextNode("\u00a0"));
}});
}else{var I=true;
A.values.visit(function(a){if(I){I=false;
}else{K.appendChild(document.createTextNode(", "));
}K.appendChild(document.createTextNode(a));
});
}}}this._dom.tableContainer.appendChild(V);
};
Exhibit.PivotTableView._sortValues=function(B,D,C){var A=[];
B.visit(D=="item"?function(E){var F=C.getObject(E,"label");
A.push({value:E,label:F!=null?F:E});
}:function(E){A.push({value:E,label:E});
});
A.sort(function(F,E){var G=F.label.localeCompare(E.label);
return G!=null?G:F.value.localeCompare(E.value);
});
return A;
};
Exhibit.PivotTableView.prototype._openPopup=function(A,E){var G=SimileAjax.DOM.getPageCoordinates(A);
var F=SimileAjax.Graphics.createBubbleForPoint(G.left+Math.round(A.offsetWidth/2),G.top+Math.round(A.offsetHeight/2),400,300);
if(E.length>1){var D=document.createElement("ul");
for(var C=0;
C<E.length;
C++){var H=document.createElement("li");
H.appendChild(Exhibit.UI.makeItemSpan(E[C],null,this._uiContext));
D.appendChild(H);
}F.content.appendChild(D);
}else{var I=document.createElement("div");
var B=this._uiContext.getLensRegistry().createLens(E[0],I,this._uiContext);
F.content.appendChild(I);
}};
Exhibit.PivotTableView.constructDom=function(C){var A=Exhibit.PivotTableView.l10n;
var B={elmt:C,children:[{tag:"div",className:"exhibit-collectionView-header",field:"collectionSummaryDiv"},{tag:"div",field:"tableContainer",className:"exhibit-pivotTableView-tableContainer"}]};
return SimileAjax.DOM.createDOMFromTemplate(B);
};


/* scatter-plot-view.js */
Exhibit.ScatterPlotView=function(C,B){this._div=C;
this._uiContext=B;
this._settings={};
this._accessors={getPointLabel:function(F,E,D){D(E.getObject(F,"label"));
},getProxy:function(F,E,D){D(F);
},getColorKey:null};
this._axisFuncs={x:function(D){return D;
},y:function(D){return D;
}};
this._axisInverseFuncs={x:function(D){return D;
},y:function(D){return D;
}};
this._colorKeyCache=new Object();
this._maxColor=0;
var A=this;
this._listener={onItemsChanged:function(){A._reconstruct();
}};
B.getCollection().addListener(this._listener);
};
Exhibit.ScatterPlotView._settingSpecs={"plotHeight":{type:"int",defaultValue:400},"bubbleWidth":{type:"int",defaultValue:400},"bubbleHeight":{type:"int",defaultValue:300},"xAxisMin":{type:"float",defaultValue:Number.POSITIVE_INFINITY},"xAxisMax":{type:"float",defaultValue:Number.NEGATIVE_INFINITY},"xAxisType":{type:"enum",defaultValue:"linear",choices:["linear","log"]},"yAxisMin":{type:"float",defaultValue:Number.POSITIVE_INFINITY},"yAxisMax":{type:"float",defaultValue:Number.NEGATIVE_INFINITY},"yAxisType":{type:"enum",defaultValue:"linear",choices:["linear","log"]},"xLabel":{type:"text",defaultValue:"x"},"yLabel":{type:"text",defaultValue:"y"},"color":{type:"text",defaultValue:"#0000aa"},"colorCoder":{type:"text",defaultValue:null}};
Exhibit.ScatterPlotView._accessorSpecs=[{accessorName:"getProxy",attributeName:"proxy"},{accessorName:"getPointLabel",attributeName:"pointLabel"},{accessorName:"getXY",alternatives:[{bindings:[{attributeName:"xy",types:["float","float"],bindingNames:["x","y"]}]},{bindings:[{attributeName:"x",type:"float",bindingName:"x"},{attributeName:"y",type:"float",bindingName:"y"}]}]},{accessorName:"getColorKey",attributeName:"colorKey",type:"text"}];
Exhibit.ScatterPlotView.create=function(D,C,B){var A=new Exhibit.ScatterPlotView(C,Exhibit.UIContext.create(D,B));
Exhibit.ScatterPlotView._configure(A,D);
A._internalValidate();
A._initializeUI();
return A;
};
Exhibit.ScatterPlotView.createFromDOM=function(D,C,B){var E=Exhibit.getConfigurationFromDOM(D);
var A=new Exhibit.ScatterPlotView(C!=null?C:D,Exhibit.UIContext.createFromDOM(D,B));
Exhibit.SettingsUtilities.createAccessorsFromDOM(D,Exhibit.ScatterPlotView._accessorSpecs,A._accessors);
Exhibit.SettingsUtilities.collectSettingsFromDOM(D,Exhibit.ScatterPlotView._settingSpecs,A._settings);
Exhibit.ScatterPlotView._configure(A,E);
A._internalValidate();
A._initializeUI();
return A;
};
Exhibit.ScatterPlotView._configure=function(A,C){Exhibit.SettingsUtilities.createAccessors(C,Exhibit.ScatterPlotView._accessorSpecs,A._accessors);
Exhibit.SettingsUtilities.collectSettings(C,Exhibit.ScatterPlotView._settingSpecs,A._settings);
A._axisFuncs.x=Exhibit.ScatterPlotView._getAxisFunc(A._settings.xAxisType);
A._axisInverseFuncs.x=Exhibit.ScatterPlotView._getAxisInverseFunc(A._settings.xAxisType);
A._axisFuncs.y=Exhibit.ScatterPlotView._getAxisFunc(A._settings.yAxisType);
A._axisInverseFuncs.y=Exhibit.ScatterPlotView._getAxisInverseFunc(A._settings.yAxisType);
var B=A._accessors;
A._getXY=function(F,E,D){B.getProxy(F,E,function(G){B.getXY(G,E,D);
});
};
};
Exhibit.ScatterPlotView._getAxisFunc=function(A){if(A=="log"){return function(B){return(Math.log(B)/Math.log(10));
};
}else{return function(B){return B;
};
}};
Exhibit.ScatterPlotView._getAxisInverseFunc=function(A){if(A=="log"){return function(B){return Math.pow(10,B);
};
}else{return function(B){return B;
};
}};
Exhibit.ScatterPlotView._colors=["FF9000","5D7CBA","A97838","8B9BBA","FFC77F","003EBA","29447B","543C1C"];
Exhibit.ScatterPlotView._mixColor="FFFFFF";
Exhibit.ScatterPlotView.evaluateSingle=function(C,B,A){return C.evaluateSingleOnItem(B,A).value;
};
Exhibit.ScatterPlotView.prototype.dispose=function(){this._uiContext.getCollection().removeListener(this._listener);
this._toolboxWidget.dispose();
this._toolboxWidget=null;
this._dom.dispose();
this._dom=null;
this._uiContext.dispose();
this._uiContext=null;
this._div.innerHTML="";
this._div=null;
};
Exhibit.ScatterPlotView.prototype._internalValidate=function(){if("getColorKey" in this._accessors){if("colorCoder" in this._settings){this._colorCoder=this._uiContext.getExhibit().getComponent(this._settings.colorCoder);
}if(this._colorCoder==null){this._colorCoder=new Exhibit.DefaultColorCoder(this._uiContext);
}}};
Exhibit.ScatterPlotView.prototype._initializeUI=function(){var A=this;
var B="_gradientPoints" in this._colorCoder?"gradient":{};
this._div.innerHTML="";
this._dom=Exhibit.ViewUtilities.constructPlottingViewDom(this._div,this._uiContext,true,{onResize:function(){A._reconstruct();
}},B);
this._toolboxWidget=Exhibit.ToolboxWidget.createFromDOM(this._div,this._div,this._uiContext);
this._dom.plotContainer.className="exhibit-scatterPlotView-plotContainer";
this._dom.plotContainer.style.height=this._settings.plotHeight+"px";
this._reconstruct();
};
Exhibit.ScatterPlotView.prototype._reconstruct=function(){var I=this;
var h=this._uiContext.getCollection();
var o=this._uiContext.getDatabase();
var z=this._settings;
var AA=this._accessors;
this._dom.plotContainer.innerHTML="";
var f=I._axisFuncs.x;
var d=I._axisFuncs.y;
var D=I._axisInverseFuncs.x;
var B=I._axisInverseFuncs.y;
var P=h.countRestrictedItems();
var J=[];
this._dom.legendWidget.clear();
if(P>0){var R=h.getRestrictedItems();
var O=(this._accessors.getColorKey!=null);
var S={};
var Z=z.xAxisMin;
var F=z.xAxisMax;
var g=z.yAxisMin;
var H=z.yAxisMax;
R.visit(function(AF){var AG=[];
I._getXY(AF,o,function(AH){if("x" in AH&&"y" in AH){AG.push(AH);
}});
if(AG.length>0){var k=null;
if(O){k=new Exhibit.Set();
AA.getColorKey(AF,o,function(AH){k.add(AH);
});
}for(var x=0;
x<AG.length;
x++){var AE=AG[x];
var y=AE.x+","+AE.y;
if(y in S){var AC=S[y];
AC.items.push(AF);
if(O){AC.colorKeys.addSet(k);
}}else{try{AE.scaledX=f(AE.x);
AE.scaledY=d(AE.y);
if(!isFinite(AE.scaledX)||!isFinite(AE.scaledY)){continue;
}}catch(AD){continue;
}var AC={xy:AE,items:[AF]};
if(O){AC.colorKeys=k;
}S[y]=AC;
Z=Math.min(Z,AE.scaledX);
F=Math.max(F,AE.scaledX);
g=Math.min(g,AE.scaledY);
H=Math.max(H,AE.scaledY);
}}}else{J.push(AF);
}});
var V=F-Z;
var M=H-g;
var i=1;
if(V>1){while(i*20<V){i*=10;
}}else{while(i<V*20){i/=10;
}}Z=Math.floor(Z/i)*i;
F=Math.ceil(F/i)*i;
var E=1;
if(M>1){while(E*20<M){E*=10;
}}else{while(E<M*20){E/=10;
}}g=Math.floor(g/E)*E;
H=Math.ceil(H/E)*E;
z.xAxisMin=Z;
z.xAxisMax=F;
z.yAxisMin=g;
z.yAxisMax=H;
var s=document.createElement("div");
s.className=SimileAjax.Platform.browser.isIE?"exhibit-scatterPlotView-canvasFrame-ie":"exhibit-scatterPlotView-canvasFrame";
this._dom.plotContainer.appendChild(s);
var L=document.createElement("div");
L.className="exhibit-scatterPlotView-canvas";
L.style.height="100%";
s.appendChild(L);
var W=document.createElement("div");
W.className="exhibit-scatterPlotView-xAxis";
this._dom.plotContainer.appendChild(W);
var C=document.createElement("div");
C.style.position="relative";
W.appendChild(C);
var c=document.createElement("div");
c.className=SimileAjax.Platform.browser.isIE?"exhibit-scatterPlotView-yAxis-ie":"exhibit-scatterPlotView-yAxis";
this._dom.plotContainer.appendChild(c);
var p=document.createElement("div");
p.style.position="relative";
p.style.height="100%";
c.appendChild(p);
var r=L.offsetWidth;
var K=L.offsetHeight;
var N=r/(F-Z);
var q=K/(H-g);
L.style.display="none";
var m=function(x,k){if(x>=1000000){return function(y){return Math.floor(k(y)/1000000)+"M";
};
}else{if(x>=1000){return function(y){return Math.floor(k(y)/1000)+"K";
};
}else{return function(y){return k(y);
};
}}};
var b=m(i,D);
var a=m(E,B);
for(var n=Z+i;
n<F;
n+=i){var G=Math.floor((n-Z)*N);
var w=document.createElement("div");
w.className="exhibit-scatterPlotView-gridLine";
w.style.width="1px";
w.style.left=G+"px";
w.style.top="0px";
w.style.height="100%";
L.appendChild(w);
var Y=document.createElement("div");
Y.className="exhibit-scatterPlotView-xAxisLabel";
Y.style.left=G+"px";
Y.innerHTML=b(n);
C.appendChild(Y);
}var u=document.createElement("div");
u.className="exhibit-scatterPlotView-xAxisName";
u.innerHTML=z.xLabel;
C.appendChild(u);
for(var l=g+E;
l<H;
l+=E){var A=Math.floor((l-g)*q);
var w=document.createElement("div");
w.className="exhibit-scatterPlotView-gridLine";
w.style.height="1px";
w.style.bottom=A+"px";
w.style.left="0px";
w.style.width="100%";
L.appendChild(w);
var Y=document.createElement("div");
Y.className="exhibit-scatterPlotView-yAxisLabel";
Y.style.bottom=A+"px";
Y.innerHTML=a(l);
p.appendChild(Y);
}var AB=document.createElement("div");
AB.className="exhibit-scatterPlotView-yAxisName";
AB.innerHTML=z.yLabel;
p.appendChild(AB);
var j={mixed:false,missing:false,others:false,keys:new Exhibit.Set()};
var X=function(AC){var y=AC.items;
var x=z.color;
if(O){x=I._colorCoder.translateSet(AC.colorKeys,j);
}var AD=AC.xy;
var k=Exhibit.ScatterPlotView._makePoint(x,Math.floor((AD.scaledX-Z)*N),Math.floor((AD.scaledY-g)*q),AC.items+": "+z.xLabel+" = "+AD.x+", "+z.yLabel+" = "+AD.y);
SimileAjax.WindowManager.registerEvent(k,"click",function(AF,AE,AG){I._openPopup(k,y);
});
L.appendChild(k);
};
for(xyKey in S){X(S[xyKey]);
}L.style.display="block";
if(O){var e=this._dom.legendWidget;
var v=this._colorCoder;
var T=j.keys.toArray().sort();
if(this._colorCoder._gradientPoints!=null){e.addGradient(this._colorCoder._gradientPoints);
}else{for(var t=0;
t<T.length;
t++){var Q=T[t];
var U=v.translate(Q);
e.addEntry(U,Q);
}}if(j.others){e.addEntry(v.getOthersColor(),v.getOthersLabel());
}if(j.mixed){e.addEntry(v.getMixedColor(),v.getMixedLabel());
}if(j.missing){e.addEntry(v.getMissingColor(),v.getMissingLabel());
}}}this._dom.setUnplottableMessage(P,J);
};
Exhibit.ScatterPlotView.prototype._openPopup=function(B,A){Exhibit.ViewUtilities.openBubbleForItems(B,A,this._uiContext);
};
Exhibit.ScatterPlotView._makePoint=function(B,E,A,D){var C=document.createElement("div");
C.innerHTML="<div class='exhibit-scatterPlotView-point' style='background: "+B+"; width: 6px; height: 6px; left: "+(E-3)+"px; bottom: "+(A+3)+"px;' title='"+D+"'></div>";
return C.firstChild;
};
