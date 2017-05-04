biigle.$viewModel("annotations-navbar",function(e){new Vue({el:e,data:{currentImageFilename:"",filenameMap:{}},methods:{updateFilename:function(e){this.currentImageFilename=this.filenameMap[e]}},watch:{currentImageFilename:function(e){document.title="Annotate "+e}},created:function(){var e=biigle.$require("biigle.events"),t=biigle.$require("annotations.imagesIds"),n=biigle.$require("annotations.imagesFilenames"),i=this.filenameMap;t.forEach(function(e,t){i[e]=n[t]}),e.$on("images.change",this.updateFilename)}})}),biigle.$viewModel("annotator-container",function(e){var t=biigle.$require("biigle.events"),n=biigle.$require("annotations.imagesIds"),i=biigle.$require("annotations.stores.images"),o=biigle.$require("volumes.urlParams");new Vue({el:e,mixins:[biigle.$require("core.mixins.loader")],components:{sidebar:biigle.$require("annotations.components.sidebar"),sidebarTab:biigle.$require("core.components.sidebarTab"),labelsTab:biigle.$require("annotations.components.labelsTab"),annotationCanvas:biigle.$require("annotations.components.annotationCanvas")},data:{currentImageIndex:0,currentImage:null,mapCenter:void 0,mapResolution:void 0},computed:{currentImageId:function(){return n[this.currentImageIndex]},currentImagePromise:function(){return i.fetchImage(this.currentImageId)}},methods:{setCurrentImage:function(e){this.currentImage=e},getNextIndex:function(e){return(e+1)%n.length},getPreviousIndex:function(e){return(e+n.length-1)%n.length},nextImage:function(){this.loading||(this.currentImageIndex=this.getNextIndex(this.currentImageIndex))},previousImage:function(){this.loading||(this.currentImageIndex=this.getPreviousIndex(this.currentImageIndex))},handleMapMoveend:function(e){this.mapCenter=e.center,this.mapResolution=e.resolution,o.set({r:Math.round(100*e.resolution),x:Math.round(e.center[0]),y:Math.round(e.center[1])})}},watch:{currentImageIndex:function(e){var i=n[this.getPreviousIndex(e)],o=n[this.getNextIndex(e)];t.$emit("images.change",this.currentImageId,i,o),this.startLoading(),this.currentImagePromise.then(this.setCurrentImage),Vue.Promise.all([this.currentImagePromise]).then(this.finishLoading)}},created:function(){this.startLoading();var e=biigle.$require("labelTrees.stores.keyboard");e.on(37,this.previousImage),e.on(32,this.nextImage),e.on(39,this.nextImage),this.currentImageIndex=n.indexOf(biigle.$require("annotations.imageId")),void 0!==o.get("r")&&(this.mapResolution=parseInt(o.get("r"),10)/100),void 0!==o.get("x")&&void 0!==o.get("y")&&(this.mapCenter=[parseInt(o.get("x"),10),parseInt(o.get("y"),10)])}})}),biigle.$component("annotations.components.annotationCanvas",function(){var e=new ol.Map({renderer:"canvas",controls:[new ol.control.Zoom,new ol.control.ZoomToExtent({tipLabel:"Zoom to show whole image",label:""}),new ol.control.FullScreen({label:""})],interactions:ol.interaction.defaults({altShiftDragRotate:!1,doubleClickZoom:!1,keyboard:!1,shiftDragZoom:!1,pinchRotate:!1,pinchZoom:!1})}),t=new ol.layer.Image;return e.addLayer(t),{components:{loaderBlock:biigle.$require("core.components.loaderBlock")},props:{image:{type:HTMLCanvasElement},loading:{type:Boolean,default:!1},center:{type:Array,default:void 0},resolution:{type:Number,default:void 0}},data:function(){return{initialized:!1}},computed:{extent:function(){return this.image?[0,0,this.image.width,this.image.height]:[0,0,0,0]},projection:function(){return new ol.proj.Projection({code:"biigle-image",units:"pixels",extent:this.extent})}},methods:{},watch:{image:function(e){t.setSource(new ol.source.Canvas({canvas:e,projection:this.projection,canvasExtent:this.extent,canvasSize:[e.width,e.height]}))},extent:function(t,n){if(t[2]!==n[2]||t[3]!==n[3]){var i=ol.extent.getCenter(t);this.initialized||(i=this.center||i,this.initialized=!0),e.setView(new ol.View({projection:this.projection,center:i,resolution:this.resolution,zoomFactor:1.5,minResolution:.25,extent:t})),void 0===this.resolution&&e.getView().fit(t,e.getSize())}}},created:function(){var t=this;biigle.$require("biigle.events").$on("sidebar.toggle",function(){t.$nextTick(function(){e.updateSize()})}),e.on("moveend",function(n){var i=e.getView();t.$emit("moveend",{center:i.getCenter(),resolution:i.getResolution()})})},mounted:function(){e.setTarget(this.$el);var t=biigle.$require("annotations.ol.ZoomToNativeControl");e.addControl(new t({label:""}))}}}),biigle.$component("annotations.components.labelsTab",{components:{labelTrees:biigle.$require("labelTrees.components.labelTrees")},props:{},data:function(){return{labelTrees:biigle.$require("annotations.labelTrees")}},methods:{handleSelectedLabel:function(e){},handleDeselectedLabel:function(e){}}}),biigle.$component("annotations.components.sidebar",{mixins:[biigle.$require("core.components.sidebar")],created:function(){}}),biigle.$declare("annotations.ol.ZoomToNativeControl",function(){function e(e){var t=e||{},n=t.label?t.label:"1",i=document.createElement("button"),o=this;i.innerHTML=n,i.title="Zoom to original resolution",i.addEventListener("click",function(){o.zoomToNative.call(o)});var a=document.createElement("div");a.className="zoom-to-native ol-unselectable ol-control",a.appendChild(i),ol.control.Control.call(this,{element:a,target:t.target}),this.duration_=void 0!==t.duration?t.duration:250}return ol.inherits(e,ol.control.Control),e.prototype.zoomToNative=function(){var e=this.getMap(),t=e.getView();if(t){var n=t.getResolution();n&&(this.duration_>0&&e.beforeRender(ol.animation.zoom({resolution:n,duration:this.duration_,easing:ol.easing.easeOut})),t.setResolution(t.constrainResolution(1)))}},e}),biigle.$declare("annotations.stores.images",function(){var e=biigle.$require("biigle.events"),t=biigle.$require("api.images"),n=window.URL||window.webkitURL;return new Vue({data:{imageCache:{},cachedIds:[],maxCachedImages:10},methods:{parseBlob:function(e){return n.createObjectURL(e.body)},createImage:function(e){var t=document.createElement("img"),n=new Vue.Promise(function(n,i){t.onload=function(){n(this)},t.onerror=function(){i("Image "+e+" could not be loaded!")}});return t.src=e,n},drawImage:function(e){var t=document.createElement("canvas");return t.width=e.width,t.height=e.height,t.getContext("2d").drawImage(e,0,0),t},fetchImage:function(e){return this.imageCache.hasOwnProperty(e)||(this.imageCache[e]=t.getFile({id:e}).then(this.parseBlob).then(this.createImage),this.cachedIds.push(e)),this.imageCache[e].then(this.drawImage)},updateCache:function(e,t,n){var i=this;this.fetchImage(e).then(function(){i.fetchImage(n)}).then(function(){i.fetchImage(t)})}},watch:{cachedIds:function(e){if(e.length>this.maxCachedImages){var t=e.shift(),i=this.imageCache[t];n.revokeObjectURL(i.src),delete this.imageCache[t]}}},created:function(){e.$on("images.change",this.updateCache)}})});