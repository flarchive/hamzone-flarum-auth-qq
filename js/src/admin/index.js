import app from "flarum/app";
import QQSettingsModal from "./components/QQSettingsModal";

app.initializers.add("hamzone-auth-qq", (app) => {
  // 区分新旧版本
  let version = app.data.settings.version.split(".")[0];

  // 旧版本
  if (version < 1) {
    app.extensionSettings["hamzone-auth-qq"] = () =>
      app.modal.show(new QQSettingsModal());
      return 
  }

  // 新版本
  app.extensionData
    .for("hamzone-auth-qq")
    .registerSetting(
      {
        setting: "hamzone-auth-qq.client_id",
        label: app.translator.trans(
          "hamzone-auth-qq.admin.qq_settings.client_id_label"
        ),
        type: "text",
      },
      30
    )
    .registerSetting(
      {
        setting: "hamzone-auth-qq.client_secret",
        label: app.translator.trans(
          "hamzone-auth-qq.admin.qq_settings.client_secret_label"
        ),
        type: "text",
      },
      30
    );
  return;
});
