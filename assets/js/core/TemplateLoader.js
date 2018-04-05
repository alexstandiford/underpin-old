class TemplateLoader {

  constructor(module,location,type,args){
    this.module = module.replace(/ /g,"-").toLowerCase();
    this.location = location === undefined ? 'default' : location;
    this.type = type === undefined ? 'default' : type;
    this.args = args === undefined ? {} : args;
    this.endpoint = "http://fytplugins.local/wp-json/underpin/v1/template/get";
  }

  loadTemplate(callback){
    jQuery.ajax({
      url:     this.endpoint,
      type:    "GET",
      data:    {
        module:   this.module,
        location: this.location,
        type:     this.type,
        args:     this.args
      },
      success: callback,
      error:   function(res){
        console.log("Ran into an error while loading in the module");
        console.log(res);
      }
    });
  };
}

module.exports = TemplateLoader;