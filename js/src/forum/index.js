import { extend } from "flarum/extend";
import app from "flarum/app";
import LogInButtons from "flarum/components/LogInButtons";
import QQLogInButton from "./components/QQLogInButton";
import SettingsPage from 'flarum/components/SettingsPage';
import Button from 'flarum/components/Button';

app.initializers.add("hamzone-auth-qq", () => {

  extend(SettingsPage.prototype, 'accountItems', (items) => {
    const {
        data: {
            attributes: {
                QQAuth: {
                    isLinked = false
                },
            },
        },
    } = app.session.user;

    items.add(`linkQQAuth`,
        <Button className={`Button QQAuthButton--${isLinked ? 'danger' : 'success'}`} icon="fab fa-qq"
            path={`/auth/${name}`} onclick={() => app.modal.show(isLinked ? UnlinkModal : LinkModal)}>
            {app.translator.trans(`hamzone-auth-qq.forum.buttons.${isLinked ? 'unlink' : 'link'}`)}
        </Button>
    );
  });

  extend(LogInButtons.prototype, "items", function (items) {
    items.add(
      "QQAndH5",
      <QQLogInButton
        className="Button LogInButton--QQ"
        icon="fab fa-qq">
        {app.translator.trans(
          "hamzone-auth-qq.forum.log_in.with_qq_button"
        )}
      </QQLogInButton>
    );
    return
  });


});
